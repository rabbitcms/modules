<?php

namespace RabbitCMS\Modules;

use Illuminate\Contracts\Support\Arrayable;

/**
 * Class Module.
 */
class Module implements Arrayable
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
    public function setEnabled(bool $value)
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
    public function getPath($path = ''): string
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
            'path' => $this->path,
            'name' => $this->name,
            'description' => $this->description,
            'namespace' => $this->namespace,
            'enabled' => $this->enabled,
            'providers' => $this->providers,
            'system' => $this->system,
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
     * @param  mixed $default
     *
     * @return mixed
     */
    public function config($key, $default = null)
    {
        return app('config')->get("module.{$this->getName()}.{$key}", $default);
    }
}