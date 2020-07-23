<?php

namespace Cronboard\Core;

use Cronboard\Core\Exception;
use Cronboard\Core\Execution\ExecuteCommandTask;
use Cronboard\Core\Execution\ExecuteExecTask;
use Cronboard\Core\Execution\ExecuteInvokableTask;
use Cronboard\Core\Execution\ExecuteJobTask;
use Cronboard\Core\Execution\ExecuteTask;
use Cronboard\Support\CommandContext;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Get remotely loaded tasks from the snapshot and attach them to the console schedule.
 */
class LoadRemoteTasksIntoSchedule
{
    protected $app;
    protected $cronboard;

    static $loadedTasks = [];

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->cronboard = $app['cronboard'];
    }

    public function execute(Schedule $schedule)
    {
        $commandContext = $this->app->make(CommandContext::class);

        // if we're not running the schedule - no need to load remote commands
        if (! $commandContext->inCommandsContext($this->getScheduleRunCommands())) {
            return $schedule;
        }

        // cronboard has not been boostrapped and we ignore pulling remote tasks
        if (! $this->cronboard->booted()) {
            return $schedule;
        }

        $customTasks = $this->cronboard->getTasks()->filter(function($task) {
            $isQueuedTask = $task->isRuntimeTask() && ! $task->isImmediateTask();
            return $task->isCronboardTask() && ! $isQueuedTask;
        });

        foreach ($customTasks as $customTask) {
            if (! $customTask->getCommand()->exists()) continue;

            if (! $this->isLoaded($schedule, $customTask)) {
                $event = $this->addTaskToSchedule($schedule, $customTask);

                if ($event) {
                    $this->cronboard->trackEvent($event);
                }

                $this->rememberAsLoaded($schedule, $customTask);
            }
        }

        return $schedule;
    }

    protected function getScheduleRunCommands(): Collection
    {
        return new Collection(['schedule:run', 'schedule:finish']);
    }

    protected function isLoaded(Schedule $schedule, Task $task)
    {
        $key = spl_object_hash($schedule);
        return in_array($task->getKey(), static::$loadedTasks[$key] ?? []);
    }

    protected function rememberAsLoaded(Schedule $schedule, Task $task)
    {
        $key = spl_object_hash($schedule);
        $loadedTasks = static::$loadedTasks[$key] ?? [];
        $loadedTasks[] = $task->getKey();
        static::$loadedTasks[$key] = $loadedTasks;
    }

    protected function addTaskToSchedule(Schedule $schedule, Task $task): ?Event
    {
        $event = $this->addEventFromTask($schedule, $task);
        if (! is_null($event)) {
            $event->linkWithTask($task);

            // apply constraints
            foreach ($task->getConstraints() as $constraintData) {
                list($constraintName, $constraintParameters) = $constraintData;
                $event = call_user_func_array([$event, $constraintName], $constraintParameters);
            }

            $event->description($task->getDetail('name'));
        }
        return $event;
    }

    protected function addEventFromTask(Schedule $schedule, Task $task): ?Event
    {
        $event = null;
        $command = $task->getCommand();

        try {
            if ($builder = $this->getTaskBuilder($command->getType(), $task)) {
                $event = $builder->attach($schedule);
                $event->setRemoteEvent(true);
            }
        } catch (ModelNotFoundException $e) {
            $eventCreationException = new Exception($e->getMessage(), 0, $e);
            $this->cronboard->reportException($eventCreationException);
        }
        
        return $event;
    }

    protected function getTaskBuilders(): array
    {
        return [
            'invokable' => ExecuteInvokableTask::class,
            'exec' => ExecuteExecTask::class,
            'job' => ExecuteJobTask::class,
            'command' => ExecuteCommandTask::class,
        ];
    }

    protected function getTaskBuilderClass(string $commandType): ?string
    {
        return Arr::get($this->getTaskBuilders(), $commandType ?: 'unknown');
    }

    protected function getTaskBuilder(string $commandType, Task $task): ?ExecuteTask
    {
        $builderClass = $this->getTaskBuilderClass($commandType);
        return $builderClass ? $builderClass::create($task, $this->app) : null;
    }
}
