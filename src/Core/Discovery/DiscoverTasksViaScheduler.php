<?php

namespace Cronboard\Core\Discovery;

use Cronboard\Commands\Builder;
use Cronboard\Commands\Command;
use Cronboard\Commands\CommandByAlias;
use Cronboard\Core\Discovery\Schedule\Recorder;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Facades\Cronboard;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskKey;
use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use ReflectionClass;
use Symfony\Component\Console\Input\ArgvInput;

class DiscoverTasksViaScheduler
{
    use IgnoresPathsOrClasses;

    protected $app;

    protected $kernel;
    protected $commandBuilder;
    protected $recorder;

    public function __construct(Container $app)
    {
        $this->app = $app;

        $this->kernel = $app->make(Kernel::class);
        $this->commandBuilder = new Builder($app);
    }

    public function getCommandsAndTasks(): array
    {
        $tasks = $this->getTasks();
        $commands = $tasks->map->getCommand()->keyBy(function($command) {
            return $command->getKey();
        })->values();
        return compact('tasks', 'commands');
    }

    public function getTasks(): Collection
    {
        // swap connectable with recorder
        $this->recorder = $recorder = new Recorder;
        $this->app['cronboard.connector']->swapTemporary($this->recorder);

        $this->invokeKernelSchedule();

        $tasks = $recorder->getEventData()->map(function($data){
            $event = $data['event'];
            $eventData = $data['eventData'];
            $constraints = $data['constraints'];

            $command = $this->buildCommandFromEventData($eventData);
            if ($command) {
                $scheduleArguments = $eventData['args'] ?? [];
                $task = $this->createTaskFromCommand($command, $scheduleArguments, $constraints, $event, $eventData);
            }
            return $task ?? null;
        })->filter()->keyBy(function($task){
            return $task->getKey();
        });

        // swap back to original connectable
        $this->app['cronboard.connector']->restore();

        return $tasks;
    }

    protected function invokeKernelSchedule()
    {
        $scheduleMethod = (new ReflectionClass($this->kernel))->getMethod('schedule');
        $scheduleMethod->setAccessible(true);
        $scheduleMethod->invoke($this->kernel, $this->recorder);
    }

    protected function getConsoleCommandByAlias(CommandByAlias $alias): ?string
    {
        $command = Arr::get($this->kernel->all(), $alias->getHandler() ?: 'missing-handler');
        return $command ? get_class($command) : null;
    }

    protected function buildCommandFromEventData(array $eventData): ?Command
    {
        $command = $this->commandBuilder->fromScheduler($eventData);
        if ($command && ($command instanceof CommandByAlias)) {
            $consoleCommandClass = $this->getConsoleCommandByAlias($command);
            if ($consoleCommandClass) {
                $command = $this->commandBuilder->fromClass($consoleCommandClass);
            }
        }

        if ($this->shouldIgnoreCommand($command)) {
            return null;
        }

        return $command;
    }

    protected function shouldIgnoreCommand(Command $command)
    {
        $handlerClass = $command->getHandler();
        if (class_exists($handlerClass)) {
            return $this->shouldIgnoreClass($handlerClass);
        }
        return false;
    }

    protected function createTaskFromCommand(Command $command, array $scheduleArguments, array $constraints, Event $event, array $eventData): Task
    {
        $constraints = Collection::wrap($constraints)->map(function($constraint){
            return [$constraint['method'], $constraint['args']];
        })->toArray();

        $callHandler = $eventData['args'][0] ?? null;
        $callArguments = $eventData['args'][1] ?? [];

        $taskParameters = $command->getParameters();

        // for jobs we need to prefill some of the parameter values
        if ($command->isJobCommand() && count($scheduleArguments) > 1) {
            $queue = Arr::get($scheduleArguments, 1);
            $connection = Arr::get($scheduleArguments, 2);
            $taskParameters->fillParameterValues(compact('queue', 'connection'), Parameters::GROUP_SCHEDULE);
        }

        if ($command->isConsoleCommand()) {
            $isCalledByCommandAlias = ! class_exists($callHandler);
            if ($isCalledByCommandAlias) {
                $callArguments = $this->extractCallArgumentsFromCommandAlias($command, $callHandler, $callArguments);
            }

            if (! empty($callArguments)) {
                $taskParameters->fillParameterGroupValuesByOrder(Parameters::GROUP_CONSOLE, $callArguments);
            }
        }

        if ($command->isInvokableCommand() && count($callArguments) > 0) {
            $taskParameters->fillParameterGroupValuesByOrder(Parameters::GROUP_INVOKE, $callArguments);
        }

        $taskKey = TaskKey::createFromEvent($event);
        return new Task($taskKey, $command, $taskParameters, $constraints);
    }

    protected function extractCallArgumentsFromCommandAlias(Command $command, string $handler, array $passedArguments = []): array
    {
        if (! empty($passedArguments)) {
            $handler .= ' ' . $this->recorder->compileConsoleParameters($passedArguments);
        }

        $argv = preg_split('/\s+/', trim(str_replace($command->getAlias(), '', $handler)), -1, PREG_SPLIT_NO_EMPTY);
        array_unshift($argv, $command->getAlias());

        $commandInputDefinition = $this->app->make($command->getHandler())->getDefinition();
        $input = new ArgvInput($argv, $commandInputDefinition);
        $arguments = array_merge($input->getArguments(), $input->getOptions());

        return array_values($arguments);
    }
}
