<?php

namespace Cronboard\Console;

use Cronboard\Console\Concerns\OutputsTasksAndCommands;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Illuminate\Console\Command;

class PreviewCommand extends Command
{
    use OutputsTasksAndCommands;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronboard:preview';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Preview what tasks and commands will be recorded by Cronboard';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $snapshot = (new DiscoverCommandsAndTasks($this->laravel))->getNewSnapshotAndStore();

        $this->outputCommands($commands = $snapshot->getCommands());
        $this->info("A total of " . $commands->count() . ' Commands will be recorded by Cronboard.');

        $this->outputTasks($tasks = $snapshot->getTasks());
        $this->info("A total of " . $tasks->count() . ' Tasks will be recorded by Cronboard.');
    }
}
