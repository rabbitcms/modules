<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Providers;

use Illuminate\Support\ServiceProvider;
use RabbitCMS\Modules\Console\SeedCommand;
use RabbitCMS\Modules\Seeders\DatabaseSeeder;

/**
 * Class SeedServiceProvider.
 * @package RabbitCMS\Modules
 */
class SeedServiceProvider extends ServiceProvider
{
    /**
     * Register the seed console command.
     */
    public function register()
    {
        $this->app->singleton(DatabaseSeeder::class);
        $this->app->singleton('command.seed', function ($app) {
            return new SeedCommand($app['db']);
        });
    }
}
