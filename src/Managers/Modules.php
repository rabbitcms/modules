<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Managers;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use RabbitCMS\Modules\Contracts\PackageContract;
use RabbitCMS\Modules\Contracts\PackagesManager;
use RabbitCMS\Modules\Module;
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
     * @inheritdoc
     */
    protected function checkPackage(string $dir, array $composer)
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
        $module['path'] = $dir;
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

    /**
     * Load modules routes.
     *
     * @param string        $scope
     */
    public function loadRoutes(string $scope = 'web')
    {
        $this->app->make('router')->group([
            'as' => $scope === 'web' ? '' : "{$scope}."
        ], function (Router $router) use ($scope) {
            $this->enabled()->each(function (Module $module) use ($scope, $router) {
                $path = $module->getPath("routes/{$scope}.php");
                if (file_exists($path)) {
                    $router->group([
                        'namespace' => $module->getNamespace() . '\\Http\\Controllers'
                    ], function (Router $router) use ($module, $path, $scope) {
                        $options = array_merge([
                            'namespace' => $scope === 'web' ? null : Str::studly($scope),
                            'as' =>  $module->getName() . '.',
                            'prefix' => $module->getName(),
                            'middleware' => $scope
                        ], $module->config("routes.{$scope}", []));
                        $router->group($options, function (Router $router) use ($path, $module) {
                            require($path);
                        });
                    });
                }
            });
        });
    }
}
