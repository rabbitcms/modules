<?php

namespace RabbitCMS\Modules\Contracts;

use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Repository;

interface ModulesManager
{
    /**
     * Get all found modules.
     *
     * @param bool $store Store result to manager.
     *
     * @return Repository
     */
    public function scan($store = true);

    /**
     * Get enabled modules.
     *
     * @return Repository
     */
    public function enabled();

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
    public function restore();

    /**
     * Store modules.
     */
    public function store();

    /**
     * @param string $name
     *
     * @return bool
     */
    public function has($name);

    /**
     * @param string $name
     *
     * @return Module
     */
    public function get($name);

    /**
     * Get all modules.
     *
     * @return Repository
     */
    public function all();
}