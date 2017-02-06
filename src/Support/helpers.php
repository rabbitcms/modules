<?php
declare(strict_types = 1);

use RabbitCMS\Modules\Support\Facade\Modules;

if (!function_exists('asset_module')) {
    /**
     * Get assett for module
     * @deprecated
     * @param string $asset
     * @param string $module [optional]
     *
     * @return string
     */
    function asset_module($asset, $module = '')
    {
        return Modules::asset($module, $asset);
    }
}

if (!function_exists('module_asset')) {
    /**
     * Get asset for module
     *
     * @param string $module
     * @param string $asset
     *
     * @return string
     */
    function module_asset(string $module, string $asset): string
    {
        return Modules::asset($module, $asset);
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
        return Modules::get($module)->getPath($path);
    }
}
