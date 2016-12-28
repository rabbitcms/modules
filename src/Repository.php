<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use RabbitCMS\Modules\Exceptions\ModuleNotFoundException;

/**
 * Class Repository.
 * @package RabbitCMS\Modules
 */
class Repository implements IteratorAggregate, Countable, Arrayable
{
    /**
     * @var Module[]
     */
    protected $modules = [];

    /**
     * Get modules count.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->modules);
    }

    /**
     * Get modules iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->modules);
    }

    /**
     * Checks if module exists.
     *
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool
    {
        return array_key_exists($name, $this->modules);
    }

    /**
     * Get module by name.
     *
     * @param string $name
     *
     * @return Module
     * @throws ModuleNotFoundException
     */
    public function get($name): Module
    {
        if (array_key_exists($name, $this->modules)) {
            return $this->modules[$name];
        }
        throw new Exceptions\ModuleNotFoundException("Module '$name' not found.");
    }

    /**
     * Run function for each module.
     *
     * @param callable $callback
     */
    public function each(callable $callback)
    {
        foreach ($this->modules as $name => $module) {
            $callback($module, $name);
        }
    }

    /**
     * Filter modules.
     *
     * @param callable $callback
     *
     * @return Repository
     */
    public function filter(callable $callback):Repository
    {
        $result = new Repository();
        foreach ($this->modules as $name => $module) {
            if ($callback($module, $name)) {
                $result->add($module);
            }
        }

        return $result;
    }

    /**
     * Add module to repository.
     *
     * @param Module[] ...$modules
     */
    public function add(Module ...$modules)
    {
        foreach ($modules as $module) {
            $this->modules[$module->getName()] = $module;
        }
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return $this->map(
            function (Module $module) {
                return $module->toArray();
            }
        );
    }

    /**
     * Map modules.
     *
     * @param callable $callback
     *
     * @return array
     */
    public function map(callable $callback): array
    {
        $result = [];
        foreach ($this->modules as $name => $module) {
            $result[$name] = $callback($module, $name);
        }

        return $result;
    }

    /**
     * Get all module.
     *
     * @return Module[]
     */
    public function all(): array
    {
        return $this->modules;
    }
}
