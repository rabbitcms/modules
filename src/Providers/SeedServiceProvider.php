<?php

namespace RabbitCMS\Modules\Providers;

use RabbitCMS\Modules\Console\SeedCommand;

class SeedServiceProvider extends \Illuminate\Database\SeedServiceProvider
{
    /**
     * Register the seed console command.
     *
     * @return void
     */
    protected function registerSeedCommand()
    {
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }
}