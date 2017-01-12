<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Contracts\PackageContract;
use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Repository;

/**
 * Class ShowModulesTrait.
 *
 * @mixin Command
 */
trait ShowModulesTrait
{
    /**
     * @param Repository $modules
     */
    protected function showModules(Repository $modules)
    {
        $this->table(
            ['Name', 'Namespace', 'Path', 'Enabled', 'Description'],
            $modules->map(function (PackageContract $module) {
                return [
                    $module->getName(),
                    $module instanceof Module ? $module->getNamespace() : '',
                    preg_replace('/^' . preg_quote(base_path() . '/', '/') . '/', '', $module->getPath()),
                    $module->isSystem() ? 'System' : ($module->isEnabled() ? 'Enabled' : 'Disabled'),
                    $module->getDescription(),
                ];
            })
        );
    }
}
