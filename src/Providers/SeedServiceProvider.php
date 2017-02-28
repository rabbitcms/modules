<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use RabbitCMS\Modules\Console\SeedCommand;
use RabbitCMS\Modules\Seeders\DatabaseSeeder;

/**
 * Class SeedServiceProvider.
 *
 * @package RabbitCMS\Modules
 */
class SeedServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    //protected $defer = true;

    /**
     * Register the seed console command.
     */
    public function register()
    {
        $this->app->singleton(DatabaseSeeder::class);
        $this->app->extend('command.seed', function () {
            return new SeedCommand($this->app['db']);
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return ['command.seed'];
    }
}
