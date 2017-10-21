<?php
declare(strict_types = 1);
namespace RabbitCMS\Modules\Console;

use Illuminate\Console\Command;
use RabbitCMS\Modules\Facades\Modules;

/**
 * Class ListCommand.
 * @package RabbitCMS\Modules
 */
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

    public function handle()
    {
        $this->showModules(Modules::all());
    }
}
