<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use RabbitCMS\Modules\Console\DisableCommand;
use RabbitCMS\Modules\Console\EnableCommand;
use RabbitCMS\Modules\Console\ListCommand;
use RabbitCMS\Modules\Facades\Modules;
use Illuminate\View\Factory as ViewFactory;

/**
 * Class ModulesServiceProvider.
 *
 * @package RabbitCMS\Modules
 * @property Application $app
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function boot()
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
        $path = dirname(__DIR__) . '/config/config.php';

        $this->mergeConfigFrom($path, 'modules');

        $this->publishes([$path => config_path('modules.php')], 'config');
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
        $this->app->beforeBootstrapping(BootProviders::class, function (Application $app) {
            $aliases = [];
            $theme = Modules::getCurrentTheme();
            if ($theme !== null) {
                $theme = Modules::getThemeByName($theme);
            }
            array_map(function (Module $module) use ($app, &$aliases, $theme) {
                $aliases += $module->getAliases();
                //Merge module config.
                if (is_file($path = $module->getPath('config/config.php'))) {
                    $this->mergeConfigFrom($path, "module.{$module->getName()}");
                }

                //Load module translation.
                if (is_dir($path = $module->getPath('resources/lang'))) {
                    $path2 = base_path("resources/lang/modules/{$module->getName()}");
                    $this->loadTranslationsFrom(is_dir($path2) ? $path2 : $path, $module->getName());
                }

                if ($theme !== null) {
                    if (is_dir($path = $theme->getPath("views/{$module->getName()}"))) {
                        $this->loadViewsFrom($path, $module->getName());
                    }
                }

                if (is_dir($path = $module->getPath('resources/views'))) {
                    $this->loadViewsFrom($path, $module->getName());
                }

                array_map(function ($class) use ($app) {
                    /* @var ServiceProvider $provider */
                    $provider = new $class($app);
                    if ($provider->isDeferred()) {
                        $app->addDeferredServices(array_fill_keys($provider->provides(), $provider));
                    } else {
                        $app->register($provider);
                    }
                }, $module->getProviders());

            }, Modules::enabled());

            AliasLoader::getInstance($aliases);

        });
    }

    /**
     * Publish modules config.
     */
    protected function publishConfigs(): void
    {
        $this->app->afterResolving('command.vendor.publish', function () {
            foreach (Modules::all() as $module) {
                if (file_exists($path = $module->getPath('config/config.php'))) {
                    $this->publishes([$path => config_path("module/{$module->getName()}.php")], 'config');
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
                if (is_dir($dir = $module->getPath('src/Database/Migrations'))
                    || is_dir($dir = $module->getPath('database/migrations'))
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
                $name = explode('::', $view->name(), 2);
                if (count($name) > 1) {
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
}
