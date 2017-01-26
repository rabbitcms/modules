<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Managers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RabbitCMS\Modules\Contracts\PackagesManager;
use RabbitCMS\Modules\Contracts\PackageContract;
use RabbitCMS\Modules\Module;
use SplFileInfo;
use RuntimeException;

/**
 * Class Modules.
 *
 * @package RabbitCMS\Modules
 */
class Modules implements PackagesManager
{
    use ManagerImpl;

    /**
     * @var callable
     */
    protected $assetResolver;

    /**
     * @inheritdoc
     */
    protected function cacheFile(): string
    {
        return 'bootstrap/cache/modules.json';
    }

    /**
     * @inheritdoc
     */
    protected function restoreItem(array $item): PackageContract
    {
        return new Module($item);
    }

    /**
     * @param SplFileInfo $file
     * @param array $composer
     * @return Module|null
     */
    protected function checkPackage(SplFileInfo $file, array $composer)
    {
        if (empty($composer['extra']['module']) || !is_array($composer['extra']['module'])) {
            return null;
        }
        $module = $composer['extra']['module'];
        if (empty($module['namespace'])) {
            if (isset($composer['autoload']['psr-4'])
                && is_array($composer['autoload']['psr-4'])
                && count($composer['autoload']['psr-4']) > 0
            ) {
                $module['namespace'] = trim(key($composer['autoload']['psr-4']), '\\');
            } else {
                throw new RuntimeException('Module namespace must be set.');
            }
        }

        if (empty($module['name'])) {
            $names = explode('/', $composer['name']);
            $module['name'] = $names[1];
        }

        $module['description'] = array_key_exists('description', $composer) ? $composer['description'] : '';
        $module['path'] = $file->getPathname();
        $module = new Module($module);

        $this->updateLink(
            $module->getPath('public'),
            public_path($this->getAssetsPath() . '/' . $module->getName())
        );

        return $module;
    }

    /**
     * @inheritdoc
     */
    public function getAssetsPath(): string
    {
        return $this->config('modulesAssets', 'modules');
    }

    /**
     * Register module providers.
     *
     * @param Application $app
     */
    public function register(Application $app)
    {
        $this->enabled()->each(function (Module $module) use ($app) {
            array_map(function ($class) use ($app) {
                /* @var ServiceProvider $provider */
                $provider = new $class($app);
                if ($provider->isDeferred()) {
                    $app->addDeferredServices(array_fill_keys($provider->provides(), $provider));
                } else {
                    $app->register($provider);
                }
            }, $module->getProviders());
        });
    }

    /**
     * Get module asset.
     *
     * @param string $module
     * @param string $path
     * @param null $secure
     * @return string
     */
    public function asset(string $module, string $path, $secure = null): string
    {
        if ($this->assetResolver && ($url = call_user_func($this->assetResolver, $module, $path, $secure)) !== null) {
            return $url;
        }

        return $this->app->make('url')->asset($this->getAssetsPath() . "/{$module}/" . $path, $secure);
    }

    /**
     * @param callable $resolver
     */
    public function setAssetResolver(callable $resolver = null)
    {
        $this->assetResolver = $resolver;
    }

    /**
     * @inheritdoc
     */
    public function config(string $key, $default = null)
    {
        return $this->app->make('config')->get('modules.' . $key, $default);
    }
}
