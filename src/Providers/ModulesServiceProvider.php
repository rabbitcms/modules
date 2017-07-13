<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Providers;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Bootstrap\BootProviders;
use Illuminate\Support\ServiceProvider;
use RabbitCMS\Modules\Console\DisableCommand;
use RabbitCMS\Modules\Console\EnableCommand;
use RabbitCMS\Modules\Console\ListCommand;
use RabbitCMS\Modules\Console\ScanCommand;
use RabbitCMS\Modules\Managers\Modules;
use RabbitCMS\Modules\Support\Facade\Modules as ModulesFacade;

/**
 * Class ModulesServiceProvider.
 * @package RabbitCMS\Modules
 * @property Application $app
 */
class ModulesServiceProvider extends ServiceProvider
{
    /**
     * @param Modules $modules
     */
    public function boot(Modules $modules)
    {
        if ($this->app->routesAreCached()) {
            $this->loadCachedRoutes();
        } else {
            $this->loadRoutes($modules);

            $this->app->booted(function () {
                $this->app->make('router')->getRoutes()->refreshNameLookups();
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
     * @param Modules $modules
     */
    protected function loadRoutes(Modules $modules)
    {
        $modules->loadRoutes('web');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        AliasLoader::getInstance(['Modules' => ModulesFacade::class,]);

        $this->registerConfig();
        $this->registerServices();
        $this->registerCommands();
        $this->registerModules();
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $path = realpath(__DIR__ . '/../../config/config.php');

        $this->mergeConfigFrom($path, "modules");

        $this->publishes([$path => config_path('modules.php')]);
    }

    /**
     * Register the service provider.
     */
    protected function registerServices()
    {
        $this->app->singleton(Modules::class, function ($app) {
            return new Modules($app);
        });
    }

    public function registerCommands()
    {
        $this->app->singleton('modules.commands.scan', function () {
            return new ScanCommand($this->app->make(Modules::class));
        });

        $this->app->singleton('modules.commands.enable', function () {
            return new EnableCommand($this->app->make(Modules::class));
        });

        $this->app->singleton('modules.commands.disable', function () {
            return new DisableCommand($this->app->make(Modules::class));
        });

        $this->app->singleton('modules.commands.list', function () {
            return new ListCommand($this->app->make(Modules::class));
        });

        $this->commands([
            'modules.commands.scan',
            'modules.commands.enable',
            'modules.commands.disable',
            'modules.commands.list',
        ]);
    }

    public function registerModules()
    {
        $this->app->beforeBootstrapping(BootProviders::class, function (Application $app) {
            $app->make(Modules::class)->register($app);
        });
    }
}
