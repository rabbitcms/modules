<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules;

use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use RabbitCMS\Modules\Contracts\PackageContract;
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
    protected $items = [];

    /**
     * Get modules count.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Get modules iterator.
     *
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
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
        return array_key_exists($name, $this->items);
    }

    /**
     * Get module by name.
     *
     * @param string $name
     *
     * @return PackageContract
     * @throws ModuleNotFoundException
     */
    public function get($name): PackageContract
    {
        if (array_key_exists($name, $this->items)) {
            return $this->items[$name];
        }
        throw new Exceptions\ModuleNotFoundException("Package '$name' not found.");
    }

    /**
     * Run function for each module.
     *
     * @param callable $callback
     */
    public function each(callable $callback)
    {
        foreach ($this->items as $name => $item) {
            $callback($item, $name);
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
        foreach ($this->items as $name => $item) {
            if ($callback($item, $name)) {
                $result->add($item);
            }
        }

        return $result;
    }

    /**
     * Add module to repository.
     *
     * @param PackageContract[] ...$items
     */
    public function add(PackageContract ...$items)
    {
        foreach ($items as $item) {
            $this->items[$item->getName()] = $item;
        }
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        return $this->map(function (PackageContract $item) {
            return $item->toArray();
        });
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
        return array_map($callback, $this->items);
    }

    /**
     * Get all module.
     *
     * @return PackageContract[]
     */
    public function all(): array
    {
        return $this->items;
    }
}
