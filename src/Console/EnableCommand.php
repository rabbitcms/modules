<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Managers\Modules;

/**
 * Class EnableCommand.
 * @package RabbitCMS\Modules
 */
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
     * @var Modules
     */
    protected $modules;

    /**
     * ScanCommand constructor.
     *
     * @param Modules $modules
     */
    public function __construct(Modules $modules)
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
