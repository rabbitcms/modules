<?php
declare(strict_types=1);

namespace RabbitCMS\Modules\Concerns;

use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Support\Facade\Modules;
use Laravel\Lumen\Application as LumenApplication;

/**
 * Trait HasModuleProvider
 *
 * @package RabbitCMS\Modules\Concerns
 */
trait HasModuleProvider
{
    /**
     * @return Module
     */
    public function module(): Module
    {
        static $module;
        return $module ?? $module = Modules::detect(get_class($this));
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations()
    {
        if (is_dir($dir = $this->module()->getPath('src/Database/Migrations'))) {
            $this->loadMigrationsFrom($dir);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $module = $this->module();

        $path = $module->getPath('config/config.php');

        if (is_file($path)) {
            $moduleName = $module->getName();

            if ($this->app instanceof LumenApplication) {
                $this->app->configure("module/{$moduleName}");
            }
            $this->mergeConfigFrom($path, "module.{$moduleName}");

            $this->publishes([$path => config_path("module/{$moduleName}.php")], 'config');
        }
    }

    /**
     * Register translations.
     */
    protected function registerTranslations()
    {
        $module = $this->module();
        $moduleName = $module->getName();
        $path = base_path("resources/lang/modules/{$moduleName}");

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, $moduleName);
        } else {
            $this->loadTranslationsFrom($module->getPath('resources/lang'), $moduleName);
        }
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $module = $this->module();
        $moduleName = $module->getName();
        $base = base_path("resources/views/modules/{$moduleName}");

        $source = $module->getPath('resources/views');

        $this->publishes([$source => $base]);

        $paths = [$base, $source];

        $this->loadViewsFrom($paths, $moduleName);
    }

    /**
     * @param callable $loader
     */
    protected function loadRoutes(callable $loader)
    {
        if (!$this->app->routesAreCached()) {
            $this->app->call($loader);
        }
    }
}