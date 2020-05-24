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
use Cronboard\Support\CommandContext;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Console\Scheduling\ScheduleRunCommand;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\ModelNotFoundException;

/**
 * Get remotely loaded tasks from the snapshot and attach them to the console schedule.
 */
class LoadRemoteTasksIntoSchedule
{
    protected $app;
    protected $cronboard;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->cronboard = $app['cronboard'];
    }

    public function execute(Schedule $schedule)
    {
        $commandContext = new CommandContext($this->app);

        // if we're not running the schedule - no need to load remote commands
        if (!$commandContext->isConsoleCommandContext(ScheduleRunCommand::class)) {
            return $schedule;
        }

        // cronboard has not been boostrapped and we ignore pulling remote tasks
        if (!($schedule instanceof CronboardSchedule)) {
            return $schedule;
        }

        $customTasks = $this->cronboard->getTasks()->filter->isCronboardTask();

        foreach ($customTasks as $customTask) {
            $event = $this->addTaskToSchedule($schedule, $customTask);
        }

        return $schedule;
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
            switch ($command->getType()) {
                case 'invokable':
                    return ExecuteInvokableTask::create($task, $this->app)->attach($schedule);
                case 'exec':
                    return ExecuteExecTask::create($task, $this->app)->attach($schedule);
                case 'job':
                    return ExecuteJobTask::create($task, $this->app)->attach($schedule);
                case 'command':
                    return ExecuteCommandTask::create($task, $this->app)->attach($schedule);
                default:
                    return null;
            }
        } catch (ModelNotFoundException $e) {
            $eventCreationException = new Exception($e->getMessage(), 0, $e);
            $this->cronboard->reportException($eventCreationException);
        }
    }
}
