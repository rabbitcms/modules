<?php

declare(strict_types=1);

namespace RabbitCMS\Modules\ComposerScripts;

use Composer\Composer;
use Composer\Package\Link;
use Composer\Package\PackageInterface;
use Composer\Package\RootPackageInterface;
use Composer\Script\Event;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\LoadConfiguration;
use Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables;
use Illuminate\Support\ServiceProvider;
use RabbitCMS\Modules\Factory;
use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Theme;

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
    private $factory;

    /**
     * @var array|Module[]
     */
    private $modules = [];

    /**
     * @var array|Theme[]
     */
    private $themes = [];

    /**
     * @var array
     */
    private $namespaces = [];

    /**
     * @var array
     */
    private $paths = [];

    /**
     * @var array
     */
    private $providers = [];

    /**
     * @var array
     */
    private $deferred = [];

    /**
     * @var array
     */
    private $aliases = [];

    /**
     * @var array
     */
    private $after = [];

    /**
     * @var array
     */
    private $packageAliases = [];

    /**
     * Handle the post-autoload-dump Composer event.
     *
     * @param  Event  $event
     */
    public static function handle(Event $event): void
    {
        $composer = $event->getComposer();
        require_once $composer->getConfig()->get('vendor-dir').'/autoload.php';
        new static($composer);
    }

    /**
     * ComposerScripts constructor.
     *
     * @param  Composer  $composer
     */
    public function __construct(Composer $composer)
    {
        $this->composer = $composer;
        $this->application = new Application(getcwd());
        $this->factory = (new Factory($this->application, false))->disableRoutes();
        $this->application->instance('modules', $this->factory);
        $this->application->bootstrapWith([
            LoadEnvironmentVariables::class,
            LoadConfiguration::class,
        ]);

        //$this->application->make(Kernel::class)->bootstrap();

        $this->clearCompiled();

        $publicPath = $this->application->publicPath().'/'.$this->factory->getModulesAssetsRoot();
        if (! is_dir($publicPath)) {
            mkdir($publicPath, 0777, true);
        }

        $this->discoverPackages();

        arsort($this->namespaces);
        arsort($this->paths);

        $this->sortModules();

        $this->aliases = array_filter($this->aliases, '\count');

        file_put_contents(
            $this->factory->getCachedModulesPath(),
            '<?php return unserialize('.var_export(serialize([
                'modules' => $this->modules,
                'themes' => $this->themes,
                'namespaces' => $this->namespaces,
                'paths' => $this->paths,
                'providers' => $this->providers,
                'deferred' => $this->deferred,
                'aliases' => $this->aliases,
            ]), true).", ['allowed_classes' => ".var_export([Theme::class, Module::class], true)."]);\n"
        );
    }

    protected function sortModules(): void
    {
        $modules = $this->modules;
        $this->modules = [];
        $filtered = [];
        while (count($modules)) {
            $module = array_shift($modules);
            $name = $module->getName();
            $after = array_reduce($this->after[$name] ?? [], function (array $after, string $package) {
                if (array_key_exists($package, $this->packageAliases)) {
                    $after[] = $this->packageAliases[$package];
                }

                return $after;
            }, []);

            if (($filtered[$name] ?? 0) < count($after) && count(array_diff($after, array_keys($this->modules)))) {
                $modules[] = $module;
                $filtered[$name] = ($filtered[$name] ?? 0) + 1;
                continue;
            }
            $this->modules[$module->getName()] = $module;
        }
    }

    protected function discoverPackages(): void
    {
        $this->checkPackage($this->composer->getPackage());
        array_map(function (PackageInterface $package) {
            $this->checkPackage($package);
        }, $this->composer->getRepositoryManager()->getLocalRepository()->getPackages());
    }

    /**
     * @param  PackageInterface  $package
     */
    protected function checkPackage(PackageInterface $package): void
    {
        if (array_key_exists('module', $package->getExtra())) {
            $this->addModule($package);
        }

        if (array_key_exists('theme', $package->getExtra())) {
            $this->addTheme($package);
        }
    }

    /**
     * @param  PackageInterface  $package
     */
    protected function addModule(PackageInterface $package): void
    {
        $extra = $package->getExtra()['module'];
        $name = $extra['name'] ?? explode('/', $package->getName())[1];
        $namespace = $extra['namespace'] ?? trim((string) key($package->getAutoload()['psr-4'] ?? []), '\\');
        $path = $package instanceof RootPackageInterface
            ? $this->application->basePath()
            : $this->composer->getInstallationManager()->getInstallPath($package);
        if (strpos($path, DIRECTORY_SEPARATOR) !== 0) {
            $path = realpath($this->application->basePath($path));
        }
        $this->packageAliases[$package->getName()] = $name;
        $this->after[$name] = array_reduce($package->getRequires(), function (array $after, Link $link) {
            return array_merge($after, [$link->getTarget()]);
        }, []);
        $this->modules[$name] = $module = new Module([
            'name' => $name,
            'namespace' => $namespace,
            'path' => $path,
        ]);

        $this->aliases[$name] = $extra['aliases'] ?? [];

        foreach ((array) ($extra['providers'] ?? []) as $provider) {
            /* @var ServiceProvider $provider */
            $provider = new $provider($this->application);
            if ($provider->isDeferred()) {
                foreach ($provider->provides() as $provide) {
                    $this->deferred[$name][$provide] = \get_class($provider);
                }
            } else {
                $this->providers[$name][] = \get_class($provider);
            }
        }

        $this->namespaces[$name] = $namespace;
        $this->paths[$name] = $path;

        if (is_dir($public = $module->getPath('public'))) {
            $this->updateLink(
                $public,
                "{$this->application->publicPath()}/{$this->factory->getModulesAssetsRoot()}/{$module->getName()}"
            );
        }
        echo 'Discovered Module: ', $name, PHP_EOL;
    }

    /**
     * @param  PackageInterface  $package
     */
    protected function addTheme(PackageInterface $package): void
    {
        $extra = $package->getExtra()['theme'];
        $name = $extra['name'] ?? explode('/', $package->getName())[1];
        $path = $package instanceof RootPackageInterface
            ? $this->application->basePath()
            : $this->composer->getInstallationManager()->getInstallPath($package);
        if (strpos($path, DIRECTORY_SEPARATOR) !== 0) {
            $path = realpath($this->application->basePath($path));
        }
        $this->themes[$name] = $theme = new Theme([
            'name' => $name,
            'path' => $path,
            'extends' => $extra['extends'] ?? null,
        ]);

        if (is_dir($public = $theme->getPath('assets'))) {
            $this->updateLink(
                $public,
                "{$this->application->publicPath()}/{$this->factory->getThemesAssetsRoot()}/{$theme->getName()}"
            );
        }
        echo 'Discovered Theme: ', $name, PHP_EOL;
    }

    /**
     * Clear the cached Laravel bootstrapping files.
     *
     * @return void
     */
    protected function clearCompiled(): void
    {
        if (file_exists($servicesPath = $this->factory->getCachedModulesPath())) {
            @unlink($servicesPath);
        }
    }

    /**
     * @param  string  $link
     * @param  string  $path
     */
    protected function updateLink(string $link, string $path): void
    {
        is_link($path) && unlink($path);
        if (is_dir($link) && $link !== $this->application->publicPath()) {
            symlink($link, $path);
        }
    }
}
