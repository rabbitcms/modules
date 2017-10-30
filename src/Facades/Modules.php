<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Facades;

use Illuminate\Support\Facades\Facade;
use RabbitCMS\Modules\Managers\Modules as ModulesManager;

/**
 * Class Modules Facade.
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
