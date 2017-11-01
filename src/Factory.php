<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use RabbitCMS\Modules\Exceptions\ModuleNotFoundException;

/**
 * Class Factory
 *
 * @package RabbitCMS\Backend
 */
class Factory
{
    /**
     * @var Application
     */
    private $app;

    /**
     * @var Module[]
     */
    private $modules = [];

    /**
     * @var Theme[]
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
    private $foundNamespaces = [];

    /**
     * @var array
     */
    private $foundPaths = [];

    /**
     * @var string[]
     */
    private $disabled = [];

    /**
     * Factory constructor.
     *
     * @param Application $app
     * @param bool        $load
     */
    public function __construct(Application $app, bool $load = true)
    {
        $this->app = $app;
        if (is_file($path = $this->getDisabledModulesPath())) {
            $this->disabled = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        }
        if ($load && is_file($this->getCachedModulesPath())) {
            /** @noinspection PhpIncludeInspection */
            [
                'modules' => $this->modules,
                'themes' => $this->themes,
                'namespaces' => $this->namespaces,
                'paths' => $this->paths
            ] = (require $this->getCachedModulesPath()) + [
                'themes' => [],
                'modules' => [],
                'paths' => [],
                'namespaces' => []
            ];

            array_walk($this->modules, function (Module $module) {
                $module->setEnabled(
                    !in_array($module->getName(), $this->disabled, true) && is_file($module->getPath('composer.json'))
                );
            });
        }
    }

    /**
     * Load modules routes.
     *
     * @param string $scope
     */
    public function loadRoutes(string $scope = 'web'): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        $this->app->make('router')->group([
            'as' => $scope === 'web' ? '' : "{$scope}."
        ], function (Router $router) use ($scope) {
            array_map(function (Module $module) use ($scope, $router) {
                if (!file_exists($path = $module->getPath("routes/{$scope}.php"))) {
                    return;
                }
                $router->group([
                    'namespace' => $module->getNamespace() . '\\Http\\Controllers'
                ], function (Router $router) use ($module, $path, $scope) {
                    $options = array_merge([
                        'namespace' => $scope === 'web' ? null : Str::studly($scope),
                        'as' => $module->getName() . '.',
                        'prefix' => $module->getName(),
                        'middleware' => $scope
                    ], $module->config("routes.{$scope}", []));
                    $router->group($options, function (
                        /** @noinspection PhpUnusedParameterInspection */
                        Router $router
                    ) use ($path, $module) {
                        /** @noinspection PhpIncludeInspection */
                        require $path;
                    });
                });
            }, $this->enabled());
        });
    }

    /**
     * Get modules assets root path.
     *
     * @return string
     */
    public function getModulesAssetsRoot(): string
    {
        return $this->app->make('config')->get('modules.modules_assets', 'modules');
    }

    /**
     * Get themes assets root path.
     *
     * @return string
     */
    public function getThemesAssetsRoot(): string
    {
        return $this->app->make('config')->get('modules.themes_assets', 'modules');
    }

    /**
     * @return null|string
     */
    public function getCurrentTheme(): ?string
    {
        return $this->app->make('config')->get('modules.theme');
    }

    /**
     * @param string $name
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function getByName(string $name): Module
    {
        if (array_key_exists($name, $this->modules)) {
            return $this->modules[$name];
        }
        throw new ModuleNotFoundException("Module '{$name}' not found.");
    }

    /**
     * @param string $namespace
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function getByNamespace(string $namespace): Module
    {
        if (array_key_exists($namespace, $this->foundNamespaces)) {
            return $this->foundNamespaces[$namespace];
        }

        foreach ($this->namespaces as $name => $ns) {
            if (strpos($namespace, $ns) === 0) {
                return $this->foundNamespaces[$ns] = $this->getByName($name);
            }
        }
        throw new ModuleNotFoundException("Module for namespace '{$namespace}' not found.");

    }

    /**
     * @param string $path
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function getByPath(string $path): Module
    {
        if (array_key_exists($path, $this->foundPaths)) {
            return $this->foundPaths[$path];
        }

        foreach ($this->paths as $name => $ns) {
            if (strpos($path, $ns) === 0) {
                return $this->foundPaths[$ns] = $this->getByName($name);
            }
        }
        throw new ModuleNotFoundException("Module for path '{$path}' not found.");
    }

    /**
     * Get theme by name.
     *
     * @param string $name
     *
     * @return Theme
     * @throws ModuleNotFoundException
     */
    public function getThemeByName(string $name): Theme
    {
        if (array_key_exists($name, $this->themes)) {
            return $this->themes[$name];
        }
        throw new ModuleNotFoundException("Theme '{$name}' not found.");
    }

    /**
     * Get module asset.
     *
     * @param string|Module $module
     * @param string        $path
     * @param bool|null     $secure
     *
     * @return string
     * @throws ModuleNotFoundException
     */
    public function asset($module, string $path, bool $secure = null): string
    {
        if (!($module instanceof Module)) {
            $module = $this->getByName($module);
        }

        $themeName = $this->getCurrentTheme();
        while ($themeName !== null) {
            $theme = $this->getThemeByName($themeName);
            if (is_file($theme->getPath("assets/{$module->getName()}/{$path}"))) {
                return $this->app->make('url')
                    ->asset("{$this->getThemesAssetsRoot()}/{$theme->getName()}/{$module->getName()}/{$path}", $secure);
            }

            $themeName = $theme->getExtends();
        }

        return $this->app->make('url')->asset("{$this->getModulesAssetsRoot()}/{$module->getName()}/{$path}", $secure);
    }

    /**
     * Get the path to the cached modules.php file.
     *
     * @return string
     */
    public function getCachedModulesPath(): string
    {
        return $this->app->bootstrapPath() . '/cache/modules.php';
    }

    /**
     * Get the path to the cached modules.php file.
     *
     * @return string
     */
    public function getDisabledModulesPath(): string
    {
        return $this->app->storagePath() . '/modules.disabled';
    }

    /**
     * Get all modules.
     *
     * @return array
     */
    public function all(): array
    {
        return $this->modules;
    }

    /**
     * Get enabled modules.
     *
     * @return array
     */
    public function enabled(): array
    {
        return array_filter($this->all(), function (Module $module) {
            return $module->isEnabled();
        });
    }

    /**
     * Enable or disable the module.
     *
     * @param string|Module $module
     * @param bool          $enabled
     *
     * @throws ModuleNotFoundException
     */
    public function enable($module, bool $enabled = true): void
    {
        if (!($module instanceof Module)) {
            $module = $this->getByName($module);
        }

        $module->setEnabled($enabled);

        if ($enabled) {
            $this->disabled = array_filter($this->disabled, function ($name) use ($module) {
                return $name !== $module->getName();
            });
        } else {
            $this->disabled[] = $module->getName();
            $this->disabled = array_unique($this->disabled);
        }

        file_put_contents($this->getDisabledModulesPath(), implode(PHP_EOL, $this->disabled));
    }
}
