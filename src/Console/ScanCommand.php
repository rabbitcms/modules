<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Managers\Modules;

/**
 * Class ScanCommand.
 * @package RabbitCMS\Modules
 */
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

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $this->modules->scan(!$this->option('pretend'));
        $this->showModules($this->modules->all());
    }
}
