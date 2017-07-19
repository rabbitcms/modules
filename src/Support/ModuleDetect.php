<?php
declare(strict_types=1);

namespace RabbitCMS\Modules\Support;

use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Support\Facade\Modules;

/**
 * Trait ModuleDetect
 *
 * @package RabbitCMS\Modules\Support
 */
trait ModuleDetect
{
    /**
     * @return Module
     */
    public function module(): Module
    {
        return Modules::detect(get_class($this));
    }
}
