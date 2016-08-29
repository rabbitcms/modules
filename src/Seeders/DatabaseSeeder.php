<?php

namespace RabbitCMS\Modules\Seeders;

use Illuminate\Database\Seeder;
use RabbitCMS\Modules\Contracts\ModulesManager;
use RabbitCMS\Modules\Module;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;

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
                $dir = $module->getPath('Database/Seeders');
                if (!is_dir($dir)) {
                    return;
                }
                $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
                foreach (new RegexIterator($iterator, '/\/Database\/Seeders\/(?<path>.*)\.php$/i', RegexIterator::GET_MATCH) as $file) {
                    $class = $module->getNamespace() . '\\Database\\Seeders\\' . str_replace('/', '\\', $file['path']);
                    $tree[$class] = $this->resolve($class);
                }
            }
        );

        foreach ($tree as $class => $seeder) {
            $this->callTree($tree, $class);
        }

        if (!empty($this->command) && ($this->command->option('class') !== 'DatabaseSeeder') && class_exists('DatabaseSeeder')) {
            $this->call('DatabaseSeeder');
        }
    }

    protected function callTree(array &$tree, $class, array $stack = [])
    {
        if (!array_key_exists($class, $tree)) {
            throw new \RuntimeException("Seeder '$class' not found.");
        }
        if ($tree[$class] === true) {
            return;
        } else if ($tree[$class] instanceof ModuleSeeder) {
            foreach ($tree[$class]->requires() as $require) {
                $this->callTree($tree, $require, $stack + [$require]);
            }
        }
        $tree[$class]->run();
        if (isset($this->command)) {
            $this->command->getOutput()->writeln("<info>Seeded:</info> $class");
        }
        $tree[$class] = true;
    }

}