<?php

namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Contracts\ModulesManager;

class ListCommand extends Command
{
    use ShowModulesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:list ';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List available modules.';

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

    public function fire()
    {
        $this->showModules($this->modules->all());
    }
}