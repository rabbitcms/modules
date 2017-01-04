<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Seeders;

use Illuminate\Database\Seeder;

/**
 * Class ModuleSeeder.
 *
 * @package RabbitCMS\Modules
 */
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