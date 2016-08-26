<?php

namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Contracts\ModulesManager;

class EnableCommand extends Command
{
    use ShowModulesTrait;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:enable {name : The name of the module.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enable the module.';

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
        $this->modules->enable($this->input->getArgument('name'));
        $this->showModules($this->modules->all());
    }
}