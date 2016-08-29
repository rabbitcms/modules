<?php

namespace RabbitCMS\Modules\Seeders;

use Illuminate\Database\Seeder;

abstract class ModuleSeeder extends Seeder
{
    /**
     * Get required seeders.
     *
     * @return string[]
     */
    public function requires()
    {
        return [];
    }
}