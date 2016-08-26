<?php

namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Module;
use RabbitCMS\Modules\Repository;

/**
 * Class ShowModulesTrait.
 *
 * @mixin Command
 */
trait ShowModulesTrait
{
    protected function showModules(Repository $modules)
    {
        $this->table(
            ['Name', 'Namespace', 'Path', 'Enabled', 'Description'],
            $modules->map(
                function (Module $module) {
                    return [
                        $module->getName(),
                        $module->getNamespace(),
                        preg_replace('/^' . preg_quote(base_path() . '/', '/') . '/', '', $module->getPath()),
                        $module->isSystem() ? 'System' : ($module->isEnabled() ? 'Enabled' : 'Disabled'),
                        $module->getDescription(),
                    ];
                }
            )
        );
    }
}