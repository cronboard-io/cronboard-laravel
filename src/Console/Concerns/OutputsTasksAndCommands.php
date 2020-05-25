<?php

namespace Cronboard\Console\Concerns;

use Cronboard\Commands\Command;
use Cronboard\Tasks\Task;
use Illuminate\Support\Collection;
use ReflectionClass;

trait OutputsTasksAndCommands
{
    use HasAccessToCronboard;

    protected function outputTasks(Collection $tasks)
    {
    	$taskRows = [];
        foreach ($tasks as $task) {
            $taskRows[] = $this->getTaskValues($task);
        }
        $this->comment("\nTASKS\n");
        $this->table($this->getTaskFields(), $taskRows);
    }

    protected function getTaskFields(): array
    {
        return ['Command', 'Constraints'];
    }

    protected function getTaskValues(Task $task): array
    {
        return [
            $task->getCommand()->getHandler(),
            $this->getTaskConstraintsOutput($task),
        ];
    }

    protected function outputCommands(Collection $commands)
    {
    	$commandRows = [];
        foreach ($commands as $command) {
            $commandRows[] = $this->getCommandValues($command);
        }

        $this->comment("\nCOMMANDS\n");
        $this->table($this->getCommandFields(), $commandRows);
    }

    protected function getCommandFields(): array
    {
        return ['Command', 'Type'];
    }

    protected function getCommandValues(Command $command): array
    {
        return [
            $command->getHandler(),
            $command->getType(),
        ];
    }

    protected function getTaskConstraintsOutput(Task $task)
    {
        return Collection::wrap($task->getConstraints())->map(function($entry) {
            return implode('', [$entry[0], '(', $this->getTaskConstraintParameterOutput($entry[1]), ')']);
        })->implode(' -> ');
    }

    protected function getTaskConstraintParameterOutput(array $parameters)
    {
        return Collection::wrap($parameters)->flatten()->map(function($parameter) {
            if (is_scalar($parameter)) {
                return $parameter;
            }
            if (is_object($parameter)) {
                return (new ReflectionClass($parameter))->getShortName();
            }
            return print_r($parameter, true);
        })->implode(', ');
    }
}
