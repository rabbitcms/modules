<?php
declare(strict_types=1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Facades\Modules;

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

    public function handle()
    {
        Modules::enable($this->input->getArgument('name'));
        $this->showModules(Modules::all());
    }
}
