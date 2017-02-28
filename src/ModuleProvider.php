<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Modules\Managers\Modules;

/**
 * Class ModuleProvider.
 * @package RabbitCMS\Modules
 */
abstract class ModuleProvider extends IlluminateServiceProvider
{
    /**
     * @var Module
     */
    protected $module;

    /**
     * @var Modules
     */
    protected $modulesManager;

    /**
     * ModuleProvider constructor.
     * @param Application $app
     */
    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->modulesManager = $this->app->make(Modules::class);
        $this->module = $this->modulesManager->get($this->name());
    }

    /**
     * Fetch module name
     *
     * @return string
     */
    abstract protected function name(): string;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTrans();
        $this->registerViews();
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
        $base = base_path("resources/views/modules/{$this->module->getName()}");

        $source = $this->module->getPath('resources/views');

        $this->publishes([$source => $base]);

        $paths = [$base, $source];

        $this->loadViewsFrom($paths, $this->module->getName());
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
     * @param  string $key
     * @param  mixed $default
     *
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        return $this->app->make('config')->get("module.{$this->module->getName()}.$key", $default);
    }
}
