<?php

namespace Cronboard\Console;

use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Tasks\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class PreviewCommand extends Command
{
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

        $commands = [];
        foreach ($snapshot->getCommands() as $command) {
            $commands[] = [
                $command->getHandler(),
                $command->getType(),
            ];
        }

        $this->comment("\nCOMMANDS\n");
        $this->table(['Command', 'Type'], $commands);
        $this->info("A total of " . count($commands) . ' Commands will be recorded by Cronboard.');

        $tasks = [];
        foreach ($snapshot->getTasks() as $task) {
            $tasks[] = [
                $task->getCommand()->getHandler(),
                $this->getTaskConstraintsOutput($task),
            ];
        }
        $this->comment("\nTASKS\n");
        $this->table(['Command', 'Constraints'], $tasks);
        $this->info("A total of " . count($tasks) . ' Tasks will be recorded by Cronboard.');
    }

    protected function getTaskConstraintsOutput(Task $task)
    {
        return Collection::wrap($task->getConstraints())->map(function($entry){
            return implode('', [$entry[0], '(', $this->getTaskConstraintParameterOutput($entry[1]), ')']);
        })->implode(' -> ');
    }

    protected function getTaskConstraintParameterOutput(array $parameters)
    {
        return Collection::wrap($parameters)->flatten()->map(function($parameter) {
            if (is_scalar($parameter)) return $parameter;
            if (is_object($parameter)) return (new ReflectionClass($parameter))->getShortName();
            return print_r($parameter, true);
        })->implode(', ');
    }
}
