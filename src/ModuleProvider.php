<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Support\ServiceProvider as IlluminateServiceProvider;
use RabbitCMS\Modules\Concerns\HasModuleProvider;

/**
 * Class ModuleProvider
 *
 * @package RabbitCMS\Modules
 * @deprecated Use AbstractModuleProvider
 */
abstract class ModuleProvider extends IlluminateServiceProvider
{
    use HasModuleProvider;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerConfig();
        $this->registerTranslations();
        $this->registerViews();
        $this->registerMigrations();
    }

    /**
     * @deprecated
     */
    public function registerTrans()
    {
        $this->registerTranslations();
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     * @deprecated
     */
    protected function config($key, $default = null)
    {
        return $this->module()->config($key, $default);
    }
}
