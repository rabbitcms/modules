<?php

declare(strict_types=1);

namespace RabbitCMS\Modules;

use DtKt\Api2\Http\Controllers\Api\GetManagerByPhone;
use Illuminate\Support\Str;
use Illuminate\Routing\Router;
use Illuminate\Foundation\{AliasLoader, Application};
use RabbitCMS\Modules\Attributes\RouterAttribute;
use RabbitCMS\Modules\Events\ThemeResolvingEvent;
use RabbitCMS\Modules\Exceptions\ModuleNotFoundException;
use RabbitCMS\Modules\Support\ClassCollector;

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

    private $providers = [];

    private $deferred = [];

    private $aliases = [];

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

    private $routes = true;

    /**
     * Factory constructor.
     *
     * @param  Application  $app
     * @param  bool  $load
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
                'paths' => $this->paths,
                'providers' => $this->providers,
                'deferred' => $this->deferred,
                'aliases' => $this->aliases,
            ] = (require $this->getCachedModulesPath()) + [
                'themes' => [],
                'modules' => [],
                'paths' => [],
                'namespaces' => [],
                'providers' => [],
                'deferred' => [],
                'aliases' => [],
            ];

            array_walk($this->modules, function (Module $module) {
                $module->setEnabled(
                    ! in_array($module->getName(), $this->disabled, true)
                    && is_file($module->getPath('composer.json'))
                );
            });
        }
    }

    /**
     * @return Factory
     * @internal
     */
    public function disableRoutes(): self
    {
        $this->routes = false;

        return $this;
    }

    /**
     * Load modules routes.
     *
     * @param  string  $scope
     * @param  string|null  $namespace
     * @param  array  $options
     */
    public function loadRoutes(string $scope = 'web', string $namespace = null, array $options = []): void
    {
        if (! $this->routes || $this->app->routesAreCached()) {
            return;
        }

        $config = array_merge(config("modules.routes.$scope", []), $options);

        if ($config === false) {
            return;
        }

        $this->app->make('router')->group(array_merge([
            'as' => $scope === 'web' ? '' : "{$scope}.",
        ], $config), function (Router $router) use ($namespace, $scope) {
            array_map(static function (Module $module) use ($namespace, $scope, $router) {
                $router->group([
                    'namespace' => $module->getNamespace('Http\\Controllers'),
                ], static function (Router $router) use ($module, $scope, $namespace) {
                    $config = $module->config("routes.{$scope}", []);

                    if ($config === false) {
                        return;
                    }
                    $namespace ??= $scope === 'web' ? null : Str::studly($scope);
                    $options = array_merge([
                        'namespace' => $namespace,
                        'as' => $module->getName().'.',
                        'prefix' => $module->getName(),
                        'middleware' => $scope,
                    ], $config);

                    if (class_exists(\ReflectionAttribute::class)) {
                        $router->group($options, static function (Router $router) use ($module, $scope) {
                            if (! is_dir($path = $module->getPath('src/Http/Controllers/'.Str::studly($scope)))) {
                                return;
                            }
                            ClassCollector::make(
                                $path,
                                $module->getNamespace('Http\\Controllers\\'.Str::studly($scope))
                            )
                                ->find()
                                ->each(function (\ReflectionClass $class) use ($scope, $router) {
                                    $attributes = $class->getAttributes(RouterAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
                                    $route = $router->name('');
                                    foreach ($attributes as $attribute) {
                                        $route = $attribute->newInstance()($class, null, $route);
                                    }

                                    $route->group(function (Router $router) use ($class) {
                                        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method){
                                            $attributes = $method->getAttributes(RouterAttribute::class, \ReflectionAttribute::IS_INSTANCEOF);
                                            $route = $router;
                                            foreach ($attributes as $attribute) {
                                                $route = $attribute->newInstance()($class, $method, $route);
                                            }
                                        }
                                    });
                                });
                        });
                    }

                    if (file_exists($path = $module->getPath("routes/{$scope}.php"))) {
                        $router->group($options, static function (Router $router) use ($path, $module) {
                            /** @noinspection PhpIncludeInspection */
                            require $path;
                        });
                    }
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
        return $this->app->make('config')->get('modules.themes_assets', 'themes');
    }

    public function getCurrentTheme(): ?string
    {
        $theme = $this->app->make('config')->get('modules.theme');

        return $this->app->make('events')->until(new ThemeResolvingEvent($theme)) ?? $theme;
    }

    /**
     * @param  string  $name
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
     * @param  string  $namespace
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
     * @param  string  $path
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
     * @param  string  $name
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
     * @param  string|Module  $module
     * @param  string  $path
     * @param  bool|null  $secure
     *
     * @return string
     * @throws ModuleNotFoundException
     */
    public function asset($module, string $path, bool $secure = null): string
    {
        if (! ($module instanceof Module)) {
            $module = $this->getByName($module);
        }

        $themeName = $this->getCurrentTheme();
        $cache = config('modules.assets_cache');
        while ($themeName !== null) {
            $theme = $this->getThemeByName($themeName);
            $file = $theme->getPath("assets/{$module->getName()}/{$path}");
            if (is_file($file)) {
                if ($cache) {
                    $time = filemtime($file);
                    $path = preg_replace('/([^\/])\.([^.]+)/', "\\1@{$time}.\\2", $path);
                    $link = $theme->getPath("assets/{$module->getName()}/{$path}");
                    if (! is_link($link)) {
                        @unlink($link);
                        symlink(basename($file), $link);
                    }
                }

                return $this->app->make('url')
                    ->asset("{$this->getThemesAssetsRoot()}/{$theme->getName()}/{$module->getName()}/{$path}", $secure);
            }

            $themeName = $theme->getExtends();
        }

        $file = $module->getPath("public/{$path}");
        if ($cache && is_file($file)) {
            $time = filemtime($file);
            $path = preg_replace('/([^\/])\.([^.]+)/', "\\1@{$time}.\\2", $path);
            $link = $module->getPath("public/{$path}");
            if (! is_link($link)) {
                @unlink($link);
                symlink(basename($file), $link);
            }
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
        return $this->app->bootstrapPath().'/cache/modules.php';
    }

    /**
     * Get the path to the cached modules.php file.
     *
     * @return string
     */
    public function getDisabledModulesPath(): string
    {
        return $this->app->storagePath().'/modules.disabled';
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
        return array_filter($this->all(), static function (Module $module) {
            return $module->isEnabled();
        });
    }

    /**
     * Enable or disable the module.
     *
     * @param  string|Module  $module
     * @param  bool  $enabled
     *
     * @throws ModuleNotFoundException
     */
    public function enable($module, bool $enabled = true): void
    {
        if (! ($module instanceof Module)) {
            $module = $this->getByName($module);
        }

        $module->setEnabled($enabled);

        if ($enabled) {
            $this->disabled = array_filter($this->disabled, static function ($name) use ($module) {
                return $name !== $module->getName();
            });
        } else {
            $this->disabled[] = $module->getName();
            $this->disabled = array_unique($this->disabled);
        }

        file_put_contents($this->getDisabledModulesPath(), implode(PHP_EOL, $this->disabled));
    }

    public function register(): void
    {
        $aliases = [];
        $providers = [];
        $deferred = [];
        array_map(function (Module $module) use (&$aliases, &$providers, &$deferred) {
            $aliases = array_merge($this->aliases[$module->getName()] ?? [], $aliases);
            $providers = array_merge($providers, $this->providers[$module->getName()] ?? []);
            $deferred = array_merge($this->deferred[$module->getName()] ?? [], $deferred);
        }, $this->enabled());

        AliasLoader::getInstance($aliases);
        $this->app->addDeferredServices($deferred);
        array_map([$this->app, 'register'], array_unique($providers));
    }
}
