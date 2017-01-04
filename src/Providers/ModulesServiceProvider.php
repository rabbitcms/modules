<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use RabbitCMS\Modules\Console\DisableCommand;
use RabbitCMS\Modules\Console\EnableCommand;
use RabbitCMS\Modules\Console\ListCommand;
use RabbitCMS\Modules\Console\ScanCommand;
use RabbitCMS\Modules\Contracts\ModulesManager;
use RabbitCMS\Modules\Manager;
use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Support\Facade\Modules;

/**
 * Class ModulesServiceProvider.
 */
class ModulesServiceProvider extends ServiceProvider
{

    /**
     * @param Router         $router
     * @param ModulesManager $modules
     */
    public function boot(Router $router, ModulesManager $modules)
    {
        $modules->enabled()->each(function (Module $module) use ($router) {
            if (file_exists($path = $module->getPath('routes/web.php'))) {
            } elseif (file_exists($path = $module->getPath('Http/routes.php'))) {
            } else {
                return;
            }

            $router->group([
                'as' => $module->getName() . '.',
                'namespace' => $module->getNamespace() . '\\Http\\Controllers'
            ], function (Router $router) use ($path, $module) {
                require($path);
            });
        });
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        AliasLoader::getInstance(['Modules' => Modules::class]);

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
        $configPath = realpath(__DIR__ . '/../../config/config.php');

        $this->mergeConfigFrom($configPath, "modules");

        $this->publishes([$configPath => config_path('modules.php')]);
    }

    /**
     * Register the service provider.
     */
    protected function registerServices()
    {
        $this->app->singleton(['modules' => ModulesManager::class], function ($app) {
            return new Manager($app);
        });
    }

    public function registerCommands()
    {
        $this->app->singleton('modules.commands.scan', function () {
            return new ScanCommand($this->app->make('modules'));
        });

        $this->app->singleton('modules.commands.enable', function () {
            return new EnableCommand($this->app->make('modules'));
        });

        $this->app->singleton('modules.commands.disable', function () {
            return new DisableCommand($this->app->make('modules'));
        });

        $this->app->singleton('modules.commands.list', function () {
            return new ListCommand($this->app->make('modules'));
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
        $this->app->booting(function (Application $app) {
            $app->make(ModulesManager::class)->register();
        });
    }
}
