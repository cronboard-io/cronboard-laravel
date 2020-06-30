<?php

namespace Cronboard\Core;

use Cronboard\Commands\Command;
use Cronboard\Console\RecordCommand;
use Cronboard\Core\Exceptions\Exception;
use Cronboard\Core\Execution\ExecuteCommandTask;
use Cronboard\Core\Execution\ExecuteExecTask;
use Cronboard\Core\Execution\ExecuteInvokableTask;
use Cronboard\Core\Execution\ExecuteJobTask;
use Cronboard\Core\Schedule as CronboardSchedule;
use Cronboard\Integrations\Integrations;
use Cronboard\Support\CommandContext;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
        $commandContext = new CommandContext($this->app);

        // if we're not running the schedule - no need to load remote commands
        if (! $commandContext->inCommandsContext($this->getScheduleRunCommands())) {
            return $schedule;
        }

        // cronboard has not been boostrapped and we ignore pulling remote tasks
        if (! ($schedule instanceof CronboardSchedule)) {
            return $schedule;
        }

        $customTasks = $this->cronboard->getTasks()->filter->isCronboardTask();

        foreach ($customTasks as $customTask) {
            if (! $customTask->getCommand()->exists()) continue;

            if (! $this->isLoaded($schedule, $customTask)) {
                $this->addTaskToSchedule($schedule, $customTask);
                $this->rememberAsLoaded($schedule, $customTask);
            }
        }

        return $schedule;
    }

    protected function getScheduleRunCommands(): Collection
    {
        return (new Collection(['schedule:run', 'schedule:finish']))
            ->merge(Integrations::getAdditionalScheduleCommands());
    }

    protected function isLoaded(Schedule $schedule, Task $task)
    {
        $key = spl_object_id($schedule);
        return in_array($task->getKey(), static::$loadedTasks[$key] ?? []);
    }

    protected function rememberAsLoaded(Schedule $schedule, Task $task)
    {
        $key = spl_object_id($schedule);
        $loadedTasks = static::$loadedTasks[$key] ?? [];
        $loadedTasks[] = $task->getKey();
        static::$loadedTasks[$key] = $loadedTasks;
    }

    protected function addTaskToSchedule(Schedule $schedule, Task $task)
    {
        $event = $this->addEventFromTask($schedule, $task);
        if (!is_null($event)) {
            $event = $event->setTask($task);

            // apply constraints
            foreach ($task->getConstraints() as $constraintData) {
                list($constraintName, $constraintParameters) = $constraintData;
                $event = call_user_func_array([$event, $constraintName], $constraintParameters);
            }

            $event->description($task->getDetail('name'));
        }
        return $event;
    }

    protected function addEventFromTask(Schedule $schedule, Task $task)
    {
        $command = $task->getCommand();

        try {
            $event = null;
            switch ($command->getType()) {
                case 'invokable':
                    $event = ExecuteInvokableTask::create($task, $this->app)->attach($schedule);
                    break;
                case 'exec':
                    $event = ExecuteExecTask::create($task, $this->app)->attach($schedule);
                    break;
                case 'job':
                    $event = ExecuteJobTask::create($task, $this->app)->attach($schedule);
                    break;
                case 'command':
                    $event = ExecuteCommandTask::create($task, $this->app)->attach($schedule);
                    break;
                default:
                    $event = null;
            }
            if ($event) {
                $event->setRemoteEvent(true);
            }
            return $event;
        } catch (ModelNotFoundException $e) {
            $eventCreationException = new Exception($e->getMessage(), 0, $e);
            $this->cronboard->reportException($eventCreationException);
        }
    }
}
