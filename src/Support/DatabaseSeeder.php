<?php

namespace RabbitCMS\Modules\Support;

use Illuminate\Database\Seeder;
use RabbitCMS\Modules\Contracts\ModulesManager;
use RabbitCMS\Modules\Module;

class DatabaseSeeder extends Seeder
{
    /**
     * @inheritdoc
     */
    public function run()
    {
        if (class_exists('DatabaseSeeder')) {
            $this->call('DatabaseSeeder');
        }
        /* @var ModulesManager $modules */
        $modules = $this->container->make('modules');

        $modules->enabled()->each(
            function (Module $module) {
                $class = $module->getNamespace() . '\\Database\\Migrations\\DatabaseSeeder';
                if (class_exists($class)) {
                    $this->call($class);
                }
            }
        );
    }
}