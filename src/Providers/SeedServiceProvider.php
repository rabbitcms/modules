<?php

namespace RabbitCMS\Modules\Providers;

use RabbitCMS\Modules\Console\SeedCommand;
use RabbitCMS\Modules\Seeders\DatabaseSeeder;

class SeedServiceProvider extends \Illuminate\Database\SeedServiceProvider
{
    /**
     * Register the seed console command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton(DatabaseSeeder::class);
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }
}