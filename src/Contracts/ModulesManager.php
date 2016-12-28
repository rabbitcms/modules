<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Contracts;

use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Repository;

/**
 * Interface ModulesManager.
 * @package RabbitCMS\Modules
 */
interface ModulesManager
{
    /**
     * Get all found modules.
     *
     * @param bool $store Store result to manager.
     *
     * @return Repository
     */
    public function scan($store = true): Repository;

    /**
     * Get enabled modules.
     *
     * @return Repository
     */
    public function enabled(): Repository;

    /**
     * Enable module.
     *
     * @param string $name
     */
    public function enable($name);

    /**
     * Disable module.
     *
     * @param string $name
     */
    public function disable($name);

    /**
     * Restore modules.
     *
     * @return bool
     */
    public function restore(): bool;

    /**
     * Store modules.
     */
    public function store();

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name): bool;

    /**
     * @param string $name
     *
     * @return Module
     */
    public function get($name): Module;

    /**
     * Get all modules.
     *
     * @return Repository
     */
    public function all(): Repository;
}
