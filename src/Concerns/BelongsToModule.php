<?php
declare(strict_types=1);

namespace RabbitCMS\Modules\Concerns;

use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Facades\Modules;

/**
 * Trait ModuleDetect
 *
 * @package RabbitCMS\Modules\Support
 */
trait BelongsToModule
{
    /**
     * Get class module.
     *
     * @param string|null $class
     *
     * @return Module
     */
    public static function module(string $class = null): Module
    {
        static $modules = [];
        $class = $class ?? static::class;

        return $modules[$class] ?? $modules[$class] = Modules::getByNamespace($class);
    }
}
