<?php

namespace Cronboard\Core\Discovery;

use Cronboard\Commands\Command;
use Cronboard\Commands\CommandByAlias;
use Cronboard\Commands\CommandNotFoundException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class Snapshot
{
    protected $container;
    protected $commands;
    protected $tasks;

    public function __construct(Container $container, Collection $commands, Collection $tasks)
    {
        $this->container = $container;
        $this->commands = $commands->keyBy->getKey();

        if ($this->validate()) {
            $this->tasks = $this->resolveCommandsInTasks($tasks, $this->getCommandsByAlias());
        } else {
            $this->tasks = $tasks;
        }
    }

    private function resolveCommandsInTasks(Collection $tasks, Collection $commands): Collection
    {
        return $tasks->map(function($task) use ($commands) {
            $taskCommand = $task->getCommand();
            if ($taskCommand instanceof CommandByAlias) {
                $resolvedCommand = $commands->get($taskCommand->getHandler());

                if (empty($resolvedCommand)) {
                    throw new CommandNotFoundException($taskCommand);
                }

                $task->setCommand($resolvedCommand);
                return $task;
            }
            return $task;
        });
    }

    public function validate(): bool
    {
        return $this->commands->filter(function($command) {
            if ($command->isConsoleCommand() || $command->isJobCommand() || $command->isInvokableCommand()) {
                return !class_exists($command->getHandler());
            }
            return false;
        })->isEmpty();
    }

    public function getCommands(): Collection
    {
        return $this->commands;
    }

    public function getCommandsByAlias(): Collection
    {
        return $this->commands->filter(function($command) {
            return $command->isConsoleCommand();
        })->keyBy(function($command) {
            return $this->container->make($command->getHandler())->getName();
        });
    }

    public function getTasksByKey(): Collection
    {
        return $this->tasks->keyBy->getKey();
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function toArray(): array
    {
        return [
            'commands' => $this->commands,
            'tasks' => $this->getTasksForStorage(),
        ];
    }

    protected function getTasksForStorage(): Collection
    {
        return $this->getTasks()->filter(function($task) {
            return !$task->isSingleExecution() && !$task->isRuntimeTask();
        });
    }

    public function getCommandByKey(string $commandKey): ?Command
    {
        return $this->commands->get($commandKey);
    }

    public static function fromArray(Container $container, array $snapshot)
    {
        return new static($container, $snapshot['commands'], $snapshot['tasks']);
    }

    public function addRemoteTasks(Collection $remoteTasks)
    {
        $remoteTaskKeys = $remoteTasks->map->getKey();

        $this->tasks = $this->tasks->filter(function($task) use ($remoteTaskKeys) {
            return $task->isApplicationTask();
        })->merge($remoteTasks);
    }
}
