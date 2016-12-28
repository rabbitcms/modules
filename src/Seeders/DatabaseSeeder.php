<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Seeders;

use Illuminate\Database\Seeder;
use RabbitCMS\Modules\Contracts\ModulesManager;
use RabbitCMS\Modules\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

/**
 * Class DatabaseSeeder.
 * @package RabbitCMS\Modules
 */
class DatabaseSeeder extends Seeder
{
    protected $run = false;

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->run) {
            return;
        }
        $this->run = true;

        /* @var ModulesManager $modules */
        $modules = $this->container->make('modules');

        $tree = [];
        $modules->enabled()->each(
            function (Module $module) use (&$tree) {
                $dir = $module->getPath('src/Database/Seeders');
                if (!is_dir($dir)) {
                    return;
                }
                $iterator = new RegexIterator(
                    new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)),
                    '/\/Database\/Seeders\/(.*)\.php$/i',
                    RegexIterator::GET_MATCH
                );
                foreach ($iterator as $file) {
                    $class = $module->getNamespace() . '\\Database\\Seeders\\' . str_replace('/', '\\', $file[1]);
                    $tree[$class] = $this->resolve($class);
                }
            }
        );

        foreach ($tree as $class => $seeder) {
            $this->callTree($tree, $class);
        }

        if (!empty($this->command)
            && ($this->command->option('class') !== 'DatabaseSeeder')
            && class_exists('DatabaseSeeder')
        ) {
            $this->call('DatabaseSeeder');
        }
    }

    /**
     * @param array $tree
     * @param string $class
     * @param array $stack
     */
    protected function callTree(array &$tree, string $class, array $stack = [])
    {
        if (!array_key_exists($class, $tree)) {
            throw new \RuntimeException("Seeder '$class' not found.");
        }
        if ($tree[$class] === true) {
            return;
        } else {
            if ($tree[$class] instanceof ModuleSeeder) {
                foreach ($tree[$class]->requires() as $require) {
                    $this->callTree($tree, $require, $stack + [$require]);
                }
            }
        }
        $tree[$class]->run();
        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeded:</info> $class");
        }
        $tree[$class] = true;
    }
}
