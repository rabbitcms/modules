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
 * @package RabbitCMS\Modules
 */
class ModulesServiceProvider extends ServiceProvider
{
    /**
     * @param Router $router
     * @param ModulesManager $modules
     */
    public function boot(Router $router, ModulesManager $modules)
    {
        $modules->enabled()->each(
            function (Module $module) use ($router) {
                $path = $module->getPath('routes/web.php');
                file_exists($path) && require($path);
            }
        );
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
        $path = realpath(__DIR__ . '/../../config/config.php');

        $this->mergeConfigFrom($path, "modules");

        $this->publishes([$path => config_path('modules.php')]);
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
