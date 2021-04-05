<?php

declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Reflector;
use Illuminate\Support\Str;
use Illuminate\Translation\Translator;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use RabbitCMS\Modules\Console\{DisableCommand, EnableCommand, ListCommand};
use RabbitCMS\Modules\Attributes\Event;
use RabbitCMS\Modules\Facades\Modules;
use RabbitCMS\Modules\Support\ClassCollector;

class ModulesServiceProvider extends EventServiceProvider
{
    public function boot(): void
    {
        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        } else {
            $this->loadRoutes();

            $this->app->booted(function () {
                $this->app->make('router')->getRoutes()->refreshNameLookups();
                $this->app->make('router')->getRoutes()->refreshActionLookups();
            });
        }
    }

    protected function loadCachedRoutes(): void
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    protected function loadRoutes(): void
    {
        Modules::loadRoutes('web');
    }

    public function register(): void
    {
        parent::register();

        $this->app->singleton('modules', function ($app) {
            return new Factory($app);
        });

        $this->app->alias('modules', Factory::class);

        $this->registerConfig();

        $this->registerCommands();
        $this->registerViewsDirectives();

        $this->publishConfigs();
        $this->loadMigrations();
        $this->registerModules();
    }

    protected function registerConfig()
    {
        $path = \dirname(__DIR__).'/config/config.php';

        $this->mergeConfigFrom($path, 'modules');

        $this->publishes([$path => \config_path('modules.php')], 'config');
    }

    public function registerCommands()
    {
        $this->app->singleton('modules.commands.enable', function () {
            return new EnableCommand();
        });

        $this->app->singleton('modules.commands.disable', function () {
            return new DisableCommand();
        });

        $this->app->singleton('modules.commands.list', function () {
            return new ListCommand();
        });

        $this->commands([
            'modules.commands.enable',
            'modules.commands.disable',
            'modules.commands.list',
        ]);
    }

    protected function registerModules(): void
    {
        $this->app->beforeBootstrapping(BootProviders::class, function () {
            $themeName = Modules::getCurrentTheme();
            $themes = [];
            while ($themeName = ($themes[] = Modules::getThemeByName($themeName))->getExtends()) {
            }

            [
                'translator' => $translators,
                'components' => $components,
                'views' => $views,
            ] = array_merge_recursive(...array_values(array_map(function (Module $module) use ($themes) {
                $data = [
                    'translator' => [],
                    'components' => [],
                    'views' => [],
                ];
                //Merge module config.
                if (is_file($path = $module->getPath('config/config.php'))) {
                    $this->mergeConfigFrom($path, "module.{$module->getName()}");
                }

                //Load module translation.
                if (is_dir($path = base_path("resources/lang/modules/{$module->getName()}"))
                    || is_dir($path = $module->getPath('resources/lang'))) {
                    $data['translator'][$module->getName()] = $path;
                }

                if (is_dir($path = $module->getPath('src/Views/Components'))) {
                    $data['components'][] = [$module->getNamespace('Views\Components'), $module->getName()];
                }

                foreach ($themes as $theme) {
                    if (is_dir($path = $theme->getPath("views/{$module->getName()}"))) {
                        $data['views'][$module->getName()][] = $path;
                    }
                }

                if (is_dir($path = $module->getPath('resources/views'))) {
                    $data['views'][$module->getName()][] = $path;
                }

                return $data;
            }, Modules::enabled())));

            $this->callAfterResolving('translator', function (Translator $translator) use ($translators) {
                foreach ($translators as $namespace => $path) {
                    $translator->addNamespace($namespace, $path);
                }
            });

            $this->callAfterResolving('blade.compiler', function (BladeCompiler $compiler) use ($components) {
                foreach ($components as [$namespace, $prefix]) {
                    $compiler->componentNamespace($namespace, $prefix);
                }
            });

            $this->callAfterResolving('view', function (\Illuminate\View\Factory $view) use ($views) {
                foreach ($views as $namespace => $path) {
                    $view->addNamespace($namespace, $path);
                }
            });

            Modules::register();
        });
    }

    protected function publishConfigs(): void
    {
        $this->app->afterResolving('command.vendor.publish', function () {
            foreach (Modules::all() as $module) {
                if (\file_exists($path = $module->getPath('config/config.php'))) {
                    $this->publishes([$path => \config_path("module/{$module->getName()}.php")], 'config');
                }
            }
        });
    }

    protected function loadMigrations(): void
    {
        $this->app->afterResolving('migrator', function (Migrator $migrator) {
            foreach (Modules::enabled() as $module) {
                if (is_dir($dir = $module->getPath('database/migrations'))
                    || is_dir($dir = $module->getPath('src/Database/Migrations'))
                ) {
                    $migrator->path($dir);
                }
            }
        });
    }

    protected function registerViewsDirectives(): void
    {
        $this->app->afterResolving('view', function (ViewFactory $view) {
            $view->composer('*', function (View $view) {
                $name = \explode('::', $view->name(), 2);
                if (\count($name) > 1) {
                    $view->with('module_name', $name[0]);
                }
            });
        });

        $this->app->afterResolving('blade.compiler', function (BladeCompiler $compiler) {
            $compiler->directive('mlang', function ($expression) {
                return "<?php echo trans(\$module_name.'::'.{$expression}); ?>";
            });
            $compiler->directive('masset', function ($expression) {
                return "<?php echo module_asset(\$module_name, {$expression}); ?>";
            });
        });
    }

    public function shouldDiscoverEvents()
    {
        return true;
    }

    public function discoverEvents(): array
    {
        return collect($this->app->make(Factory::class)->enabled())
            ->reduce(function ($discovered, Module $module) {
                return array_merge_recursive($discovered, $this->getListenerEvents($module));
            }, []);
    }

    /**
     * Get all of the listeners and their corresponding events.
     */
    protected function getListenerEvents(Module $module): array
    {
        if (! is_dir($module->getPath('src/Listeners')) || ! $module->getExtra('listeners', true)) {
            return [];
        }

        return ClassCollector::make($module->getPath('src/Listeners'), $module->getNamespace('Listeners'))
            ->find()
            ->reduce(function (Collection $listenerEvents, \ReflectionClass $class) use ($module) {
                foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
                    if (class_exists(\ReflectionAttribute::class, false)) {
                        $attributes = $method->getAttributes(Event::class, \ReflectionAttribute::IS_INSTANCEOF);
                        if (count($attributes) > 0) {
                            $listenerEvents["{$class->name}@{$method->name}"] =
                                array_map(function (\ReflectionAttribute $attribute) use (
                                    $class,
                                    $method,
                                    $module
                                ) {
                                    return $attribute->newInstance()->getEvent($class, $method, $module);
                                }, $attributes);
                            continue;
                        }
                    }

                    if (empty($event) && (! Str::is('handle*',
                                $method->name) || ! isset($method->getParameters()[0]))) {
                        continue;
                    }

                    $listenerEvents["{$class->name}@{$method->name}"] =
                        [Reflector::getParameterClassName($method->getParameters()[0])];
                }

                return $listenerEvents;
            }, collect())
            ->filter()
            ->reduce(function (array $list, string|array $events, string $listener) {
                return array_reduce((array) $events, function (array $list, $event) use ($listener) {
                    $list[$event] = array_merge($list[$event] ?? [], [$listener]);

                    return $list;
                }, $list);
            }, []);
    }
}
