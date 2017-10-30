<?php
declare(strict_types=1);

namespace RabbitCMS\Modules;

/**
 * Class Theme
 *
 * @package RabbitCMS\Modules
 */
class Theme
{
    /**
     * Theme path.
     *
     * @var string
     */
    protected $path;

    /**
     * Theme name.
     *
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $extends;

    /**
     * Module constructor.
     *
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->path = $options['path'];
        $this->name = $options['name'] ?? basename($this->path);
        $this->extends = $options['extends'] ?? null;
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
     * @return null|string
     */
    public function getExtends(): ?string
    {
        return $this->extends;
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
        return app('config')->get("theme.{$this->getName()}.{$key}", $default);
    }
}