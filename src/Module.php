<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

use RabbitCMS\Modules\Contracts\PackageContract;
use RabbitCMS\Modules\Support\Facade\Modules;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\View as ViewFacade;

/**
 * Class Module.
 */
class Module implements PackageContract
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
     * Module description.
     *
     * @var string
     */
    protected $description;

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
    protected $enabled;

    /**
     * Module providers.
     *
     * @var string[]
     */
    protected $providers;

    /**
     * @var bool
     */
    protected $system;

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
        $this->description = array_key_exists('description', $options) ? $options['description'] : '';
        $this->enabled = array_key_exists('enabled', $options) ? $options['enabled'] : true;
        $this->providers = array_key_exists('providers', $options) ? (array)$options['providers'] : [];
        $this->system = array_key_exists('system', $options) ? $options['system'] : false;
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
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->system;
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
     * Get modules providers.
     *
     * @return string[]
     */
    public function getProviders(): array
    {
        return $this->providers;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return [
            'path'        => $this->path,
            'name'        => $this->name,
            'description' => $this->description,
            'namespace'   => $this->namespace,
            'enabled'     => $this->enabled,
            'providers'   => $this->providers,
            'system'      => $this->system,
        ];
    }

    /**
     * Get module description.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
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
        return app('config')->get("module.{$this->getName()}.{$key}", $default);
    }

    /**
     * @param string    $path
     * @param bool|null $secure
     *
     * @return string
     */
    public function asset(string $path, ?bool $secure = null): string
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
    public function view(string $view, array $data = []): ViewContract
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
    public function viewName($view): string
    {
        return $this->getName() . '::' . $view;
    }
}
