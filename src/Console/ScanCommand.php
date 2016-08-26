<?php

namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Contracts\ModulesManager;

class ScanCommand extends Command
{
    use ShowModulesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:scan {--pretend : Only show found modules.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rescan available modules';

    /**
     * @var ModulesManager
     */
    protected $modules;

    /**
     * ScanCommand constructor.
     *
     * @param ModulesManager $modules
     */
    public function __construct(ModulesManager $modules)
    {
        parent::__construct();
        $this->modules = $modules;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function fire()
    {
        $this->showModules($this->modules->scan(!$this->option('pretend')));
    }
}