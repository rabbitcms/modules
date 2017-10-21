<?php
declare(strict_types=1);

namespace RabbitCMS\Modules\ComposerScripts;

use Composer\Composer;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Console\Kernel;
use RabbitCMS\Modules\Factory;
use RabbitCMS\Modules\Module;

/**
 * Class PostAutoloadDump
 *
 * @package RabbitCMS\Modules\ComposerScripts
 */
class PostAutoloadDump
{
    /**
     * @var Composer
     */
    private $composer;

    /**
     * @var Application
     */
    private $application;

    /**
     * @var Factory
     */
    private $modules;

    /**
     * @var array
     */
    private $newModules = [];

    private $namespaces = [];

    private $paths = [];

    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  \Composer\Script\Event $event
     *
     * @return void
     */
    public static function handle(Event $event)
    {
        $composer = $event->getComposer();
        require_once $composer->getConfig()->get('vendor-dir') . '/autoload.php';
        new static($composer);
    }

    /**
     * ComposerScripts constructor.
     *
     * @param Composer $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
        $this->application = new Application(getcwd());
        $this->modules = new Factory($this->application, false);
        $this->application->instance('modules', $this->modules);
        $this->application->make(Kernel::class)->bootstrap();

        $this->clearCompiled();

        $publicPath = $this->application->publicPath() . '/' . $this->modules->getAssetsRoot();
        if (!is_dir($publicPath)) {
            mkdir($publicPath, 0777, true);
        }

        $this->discoverPackages();


        arsort($this->namespaces);
        arsort($this->paths);

        file_put_contents(
            $this->modules->getCachedModulesPath(),
            '<?php return unserialize(' . var_export(serialize([
                'modules' => $this->newModules,
                'namespaces' => $this->namespaces,
                'paths' => $this->paths
            ]), true) . ");\n"
        );
    }

    protected function discoverPackages(): void
    {
        $this->checkPackage($this->composer->getPackage());
        array_map(function (PackageInterface $package) {
            $this->checkPackage($package);
        }, $this->composer->getRepositoryManager()->getLocalRepository()->getPackages());
    }

    /**
     * @param PackageInterface $package
     */
    protected function checkPackage(PackageInterface $package): void
    {
        if (array_key_exists('module', $package->getExtra())) {
            $this->addModule($package);
        }
    }

    /**
     * @param PackageInterface $package
     */
    protected function addModule(PackageInterface $package): void
    {
        $extra = $package->getExtra()['module'];
        $name = $extra['name'] ?? explode('/', $package->getName())[1];
        $namespace = $extra['namespace'] ?? trim((string)key($package->getAutoload()['psr-4'] ?? []), '\\');
        $path = $package instanceof RootPackageInterface
            ? $this->application->basePath()
            : $this->composer->getInstallationManager()->getInstallPath($package);
        if (strpos($path, DIRECTORY_SEPARATOR) !== 0) {
            $path = realpath($this->application->basePath($path));
        }
        $this->newModules[$name] = $module = new Module([
            'name' => $name,
            'namespace' => $namespace,
            'path' => $path,
            'providers' => (array)($extra['providers'] ?? []),
            'aliases' => $extra['aliases'] ?? []
        ]);
        $this->namespaces[$name] = $namespace;
        $this->paths[$name] = $path;

        if (is_dir($public = $module->getPath('public'))) {
            $this->updateLink(
                $public,
                $this->application->publicPath() . '/' . $this->modules->getAssetsRoot() . '/' . $module->getName()
            );
        }
        echo 'Discovered Module: ', $name, PHP_EOL;
    }

    /**
     * Clear the cached Laravel bootstrapping files.
     *
     * @return void
     */
    protected function clearCompiled()
    {
        if (file_exists($servicesPath = $this->modules->getCachedModulesPath())) {
            @unlink($servicesPath);
        }
    }

    /**
     * @param string $link
     * @param string $path
     */
    protected function updateLink(string $link, string $path)
    {
        is_link($path) && unlink($path);
        if (is_dir($link) && $link !== $this->application->publicPath()) {
            symlink($link, $path);
        }
    }
}
