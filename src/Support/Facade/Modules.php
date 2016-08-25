<?php

namespace RabbitCMS\Modules\Support\Facade;

use Illuminate\Support\Facades\Facade;
use RabbitCMS\Modules\Contracts\ModulesManager;

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