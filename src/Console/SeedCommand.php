<?php

namespace RabbitCMS\Modules\Console;


use RabbitCMS\Modules\Seeders\DatabaseSeeder;

class SeedCommand extends \Illuminate\Database\Console\Seeds\SeedCommand
{
    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        foreach ($options as &$option) {
            if($option[0] === 'class') {
                $option[4] = DatabaseSeeder::class;
            }
        }

        return $options;
    }
}