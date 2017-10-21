<?php
declare(strict_types=1);

namespace RabbitCMS\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use RabbitCMS\Modules\Module;

/**
 * Class Modules
 *
 * @package RabbitCMS\Modules\Facades
 * @method static Module getByName(string $name)
 * @method static Module getByNamespace(string $namespace)
 * @method static Module getByPath(string $path)
 * @method static Module[] all()
 * @method static Module[] enabled()
 * @method static void loadRoutes(string $scope = 'web')
 * @method static string asset(string|Module $module, string $path, ?bool $secure = null)
 * @method static void enable(string|Module $module, bool $enabled = true)
 */
class Modules extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'modules';
    }
}
