<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Facades\Modules;

/**
 * Class DisableCommand
 * @package RabbitCMS\Modules
 */
class DisableCommand extends Command
{
    use ShowModulesTrait;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modules:disable {name : The name of the module.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable the module.';

    public function handle()
    {
        Modules::enable($this->input->getArgument('name'), false);
        $this->showModules(Modules::all());
    }
}
