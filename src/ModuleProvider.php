<?php

namespace RabbitCMS\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Modules\Contracts\ModulesManager;

abstract class ModuleProvider extends IlluminateServiceProvider
{
    /**
     * @var Module
     */
    protected $module;

    /**
     * @var ModulesManager
     */
    protected $modulesManager;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->modulesManager = $this->app->make(ModulesManager::class);
        $this->module = $this->modulesManager->get($this->name());
    }

    /**
     * Fetch module name
     *
     * @return string
     */
    abstract protected function name();

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();

        if (is_dir($dir = $this->module->getPath('Database/Migrations'))) {
            $this->loadMigrationsFrom($dir);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $configPath = $this->module->getPath('Config/config.php');

        if (is_file($configPath)) {
            $this->mergeConfigFrom($configPath, "module.{$this->module->getName()}");

            $this->publishes([$configPath => config_path("module/{$this->module->getName()}.php")]);
        }
    }

    /**
     * Register translations.
     */
    protected function registerTranslations()
    {
        $langPath = base_path("resources/lang/modules/{$this->module->getName()}");

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->module->getName());
        } else {
            $this->loadTranslationsFrom($this->module->getPath('Resources/lang'), $this->module->getName());
        }
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $viewPath = base_path("resources/views/modules/{$this->module->getName()}");

        $sourcePath = $this->module->getPath('Resources/views');

        $this->publishes([$sourcePath => $viewPath]);

        $this->loadViewsFrom([$viewPath, $sourcePath], $this->module->getName());
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    protected function config($key, $default = null)
    {
        $this->app->make('config')->get("module.{$this->module->getName()}.$key", $default);
    }
}