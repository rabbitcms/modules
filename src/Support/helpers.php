<?php
declare(strict_types = 1);

use RabbitCMS\Modules\Facades\Modules;
use RabbitCMS\Modules\Module;

if (!function_exists('module_asset')) {
    /**
     * Get asset for module
     *
     * @param string|Module    $module
     * @param string    $asset
     *
     * @param bool|null $secure
     *
     * @return string
     */
    function module_asset($module, string $asset, ?bool $secure = null): string
    {
        return Modules::asset($module, $asset, $secure);
    }
}

if (!function_exists('module_path')) {
    /**
     * Get path for module
     *
     * @param string $module
     * @param string $path
     *
     * @return string
     */
    function module_path(string $module, string $path = ''): string
    {
        return Modules::getByName($module)->getPath($path);
    }
}
