<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Config;
use RabbitCMS\Modules\Facades\Modules;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * Class Module.
 */
class Module
{
    /**
     * Module path.
     *
     * @var string
     */
    protected $path;

    /**
     * Module name.
     *
     * @var string
     */
    protected $name;

    /**
     * Module namespace.
     *
     * @var string
     */
    protected $namespace;

    /**
     * Module enabled.
     *
     * @var bool
     */
    protected $enabled = true;

    /**
     * Module providers.
     *
     * @var string[]
     */
    protected $providers = [];

    /**
     * @var array
     */
    protected $aliases = [];

    /**
     * Module constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->path = $options['path'];
        $this->namespace = $options['namespace'];
        $this->name = array_key_exists('name', $options) ? $options['name'] : basename($this->path);
        $this->providers = array_key_exists('providers', $options) ? (array)$options['providers'] : [];
        $this->aliases = array_key_exists('aliases', $options) ? (array)$options['aliases'] : [];
    }

    /**
     * Get module name.
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get module namespace.
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set enabled module.
     *
     * @param bool $value
     */
    public function setEnabled(bool $value = true)
    {
        $this->enabled = $value;
    }

    /**
     * Get modules providers.
     *
     * @return string[]
     */
    public function getProviders()
    {
        return $this->providers;
    }

    /**
     * @return array
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Get module path.
     *
     * @param string $path
     *
     * @return string
     */
    public function getPath(string $path = ''): string
    {
        return $this->path . ($path ? '/' . $path : '');
    }

    /**
     * Get the specified configuration value.
     *
     * @param  string $key
     * @param  mixed  $default
     *
     * @return mixed
     */
    public function config(string $key, $default = null)
    {
        return Config::get("module.{$this->getName()}.{$key}", $default);
    }

    /**
     * @param string    $path
     *
     * @param bool|null $secure
     *
     * @return string
     */
    public function asset(string $path, ?bool $secure = null)
    {
        return Modules::asset($this, $path, $secure);
    }

    /**
     * @param string $key
     * @param array  $parameters
     * @param null   $locale
     *
     * @return array|null|string
     */
    public function trans(string $key, array $parameters = [], $locale = null)
    {
        return trans($this->getName() . '::' . $key, $parameters, $locale);
    }

    /**
     * @param string $view
     * @param array  $data
     *
     * @return ViewContract
     */
    protected function view(string $view, array $data = []): ViewContract
    {
        return ViewFacade::make($this->viewName($view), $data, []);
    }

    /**
     * Get module view name.
     *
     * @param string $view
     *
     * @return string
     */
    protected function viewName($view): string
    {
        return $this->getName() . '::' . $view;
    }
}
