<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Support\Facade;

use Illuminate\Support\Facades\Facade;
use RabbitCMS\Modules\Contracts\ModulesManager;

/**
 * Class Modules Facade.
 * @method static has(string $name): bool
 */
class Modules extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return ModulesManager::class;
    }
}
