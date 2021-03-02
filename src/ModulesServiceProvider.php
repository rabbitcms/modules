<?php

declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Foundation\Support\Providers\EventServiceProvider;
use Illuminate\Routing\Matching\ValidatorInterface;
use Illuminate\Routing\Route;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory as ViewFactory;
use RabbitCMS\Modules\Console\DisableCommand;
use RabbitCMS\Modules\Console\EnableCommand;
use RabbitCMS\Modules\Console\ListCommand;
use RabbitCMS\Modules\Facades\Modules;
use RabbitCMS\Modules\Http\Validators\ThemeValidator;
use RabbitCMS\Modules\Support\DiscoverEvents;

/**
 * Class ModulesServiceProvider.
 *
 * @package RabbitCMS\Modules
 * @property Application $app
 */
class ModulesServiceProvider extends EventServiceProvider
{
    public function boot()
    {
        //Allow to adding validators.
        Route::macro('appendValidator', static function (ValidatorInterface $validator) {
            Route::getValidators();
            Route::$validators[] = $validator;
        });

        Route::appendValidator(new ThemeValidator());

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

    /**
     * Load the cached routes for the application.
     *
     * @return void
     */
    protected function loadCachedRoutes()
    {
        $this->app->booted(function () {
            require $this->app->getCachedRoutesPath();
        });
    }

    /**
     * Load the application routes.
     *
     */
    protected function loadRoutes()
    {
        Modules::loadRoutes('web');
    }

    /**
     * Register the service provider.
     */
    public function register()
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

    /**
     * Register config.
     */
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

    /**
     * Register modules providers.
     */
    protected function registerModules(): void
    {
        $this->app->beforeBootstrapping(BootProviders::class, function () {
            $themeName = Modules::getCurrentTheme();
            $themes = [];
            while ($themeName !== null) {
                $themes[] = $theme = Modules::getThemeByName($themeName);
                $themeName = $theme->getExtends();
            }
            \array_map(function (Module $module) use ($themes) {
                //Merge module config.
                if (is_file($path = $module->getPath('config/config.php'))) {
                    $this->mergeConfigFrom($path, "module.{$module->getName()}");
                }

                //Load module translation.
                if (\is_dir($path = $module->getPath('resources/lang'))) {
                    $path2 = \base_path("resources/lang/modules/{$module->getName()}");
                    $this->loadTranslationsFrom(\is_dir($path2) ? $path2 : $path, $module->getName());
                }

                foreach ($themes as $theme) {
                    if (\is_dir($path = $theme->getPath("views/{$module->getName()}"))) {
                        $this->loadViewsFrom($path, $module->getName());
                    }
                }

                if (\is_dir($path = $module->getPath('resources/views'))) {
                    $this->loadViewsFrom($path, $module->getName());
                }

                //\array_map(function ($class) use ($app) {
                //    /* @var ServiceProvider $provider */
                //    $provider = new $class($app);
                //    if ($provider->isDeferred()) {
                //        $app->addDeferredServices(\array_fill_keys($provider->provides(), $provider));
                //    } else {
                //        $app->register($provider);
                //    }
                //}, $module->getProviders());

            }, Modules::enabled());
            Modules::register();
            // AliasLoader::getInstance($aliases);
        });
    }

    /**
     * Publish modules config.
     */
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

    /**
     * Load modules migrations.
     */
    protected function loadMigrations(): void
    {
        $this->app->afterResolving('migrator', function (Migrator $migrator) {
            foreach (Modules::enabled() as $module) {
                if (\is_dir($dir = $module->getPath('src/Database/Migrations'))
                    || \is_dir($dir = $module->getPath('database/migrations'))
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

    /**
     * Discover the events and listeners for the application.
     *
     * @return array
     */
    public function discoverEvents()
    {
        return collect($this->app->make(Factory::class)->enabled())
            ->filter(function (Module $module) {
                return is_dir($module->getPath('src/Listeners')) && $module->getExtra('listeners');
            })
            ->reduce(function ($discovered, Module $module) {
                return array_merge_recursive(
                    $discovered,
                    DiscoverEvents::within($module->getPath('src/Listeners'), $module->getNamespace().'\Listeners')
                );
            }, []);
    }
}
