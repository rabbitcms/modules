<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Module;

/**
 * Class ShowModulesTrait.
 *
 * @mixin Command
 */
trait ShowModulesTrait
{
    /**
     * @param Module[] $modules
     */
    protected function showModules(array $modules)
    {
        $regex = '#^' . preg_quote(base_path(), '#') . '#';
        $this->table(
            ['Name', 'Namespace', 'Path', 'Enabled'],
            array_map(function (Module $module) use ($regex) {
                return [
                    $module->getName(),
                    $module->getNamespace(),
                    preg_replace($regex, '{$root}', $module->getPath()),
                    $module->isEnabled() ? 'Enabled' : 'Disabled',
                ];
            }, $modules)
        );
    }
}
