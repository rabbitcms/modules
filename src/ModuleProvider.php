<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Modules\Concerns\BelongsToModule;
use RabbitCMS\Modules\Managers\Modules;

/**
 * Class ModuleProvider.
 *
 * @package RabbitCMS\Modules
 */
abstract class ModuleProvider extends IlluminateServiceProvider
{
    use BelongsToModule;

    /**
     * @var Module
     */
    protected $module;

    /**
     * @var Modules
     * @deprecated
     */
    protected $modulesManager;

    /**
     * ModuleProvider constructor.
     *
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->modulesManager = $this->app->make(Modules::class);
        $this->module = static::module();
    }

    /**
     * Fetch module name
     *
     * @return string
     */
    protected function name(): string
    {
        return static::module()->getName();
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTrans();
        $this->registerViews();
        $this->registerMigrations();
    }

    /**
     * Register migrations.
     */
    protected function registerMigrations()
    {
        if (is_dir($dir = $this->module->getPath('src/Database/Migrations'))
            || is_dir($dir = $this->module->getPath('database/migrations'))
        ) {
            $this->loadMigrationsFrom($dir);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $path = $this->module->getPath('config/config.php');

        if (is_file($path)) {
            $this->mergeConfigFrom($path, "module.{$this->module->getName()}");

            $this->publishes([$path => config_path("module/{$this->module->getName()}.php")]);
        }
    }

    /**
     * Register translations.
     */
    protected function registerTrans()
    {
        $path = base_path("resources/lang/modules/{$this->module->getName()}");

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, $this->module->getName());
        } else {
            $this->loadTranslationsFrom($this->module->getPath('resources/lang'), $this->module->getName());
        }
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        if (is_dir($source = $this->module->getPath('resources/views'))) {
            $this->loadViewsFrom($source, $this->module->getName());
        }
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

    /**
     * Get the specified configuration value.
     *
     * @deprecated use Module::config()
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function config(string $key, $default = null)
    {
        return static::module()->config($key, $default);
    }
}
