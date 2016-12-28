<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Modules\Contracts\ModulesManager;

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
     * @var ModulesManager
     */
    protected $modulesManager;

    /**
     * ModuleProvider constructor.
     * @param Application $app
     */
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
    abstract protected function name(): string;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTrans();
        $this->registerViews();
        $directory = $this->module->getPath('database/migrations');
        if (is_dir($directory)) {
            $this->loadMigrationsFrom($directory);
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig()
    {
        $config_path = $this->module->getPath('config/config.php');

        if (is_file($config_path)) {
            $this->mergeConfigFrom($config_path, "module.{$this->module->getName()}");

            $this->publishes([$config_path => config_path("module/{$this->module->getName()}.php")]);
        }
    }

    /**
     * Register translations.
     */
    protected function registerTrans()
    {
        $lang_path = base_path("resources/lang/modules/{$this->module->getName()}");

        if (is_dir($lang_path)) {
            $this->loadTranslationsFrom($lang_path, $this->module->getName());
        } else {
            $this->loadTranslationsFrom($this->module->getPath('resources/lang'), $this->module->getName());
        }
    }

    /**
     * Register views.
     */
    protected function registerViews()
    {
        $base_path = base_path("resources/views/modules/{$this->module->getName()}");

        $source_path = $this->module->getPath('resources/views');

        $this->publishes([$source_path => $base_path]);

        $this->loadViewsFrom([$base_path, $source_path], $this->module->getName());
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
