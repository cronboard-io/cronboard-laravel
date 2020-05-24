<?php

namespace Cronboard\Core\Execution\Listeners;

use Cronboard\Core\Execution\Events\TaskFailed;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\Task;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use ReflectionObject;

class CommandEventSubscriber extends EventSubscriber
{
    public function handleCommandStarting(CommandStarting $event)
    {
        $this->registerOutputStreamFromEvent($event);
        $this->startTask($this->getTaskFromEventAndVerify($event));
    }

    protected function registerOutputStreamFromEvent(CommandStarting $event)
    {
        if ((new ReflectionObject($event))->getProperty('output')->isPublic()) {
            $this->cronboard->registerOutputStream($event->output);    
        }
    }

    public function handleCommandFinished(CommandFinished $event)
    {
        $this->endTask($this->getTaskFromEventAndVerify($event), ['exitCode' => $event->exitCode]);
    }

    protected function getTaskFromEvent($event): ?Task
    {
        $task = $event->task ?? null;
        if (!empty($task) && $task instanceof Task) {
            return $task;
        }
        return (new Resolver($this->cronboard))->resolveFromEnvironment();
    }

    public function handleTaskStarting(TaskStarting $event)
    {
        $this->startTask($this->getTaskFromEvent($event));
    }

    public function handleTaskFinished(TaskFinished $event)
    {
        $this->endTask($this->getTaskFromEvent($event));
    }

    public function handleTaskFailed(TaskFailed $event)
    {
        $this->failTask($this->getTaskFromEvent($event));
    }

    protected function isTaskSupported(Task $task): bool
    {
        return $task->getCommand()->isConsoleCommand();
    }

    protected function getTaskFromEventAndVerify($event)
    {
        $task = $this->getTaskFromEvent($event);
        if (!empty($task) && $this->isTaskSupported($task)) {
            // context does not match the command in the event;
            // most likely the event was for the schedule:finish command
            if ($task->getCommand()->getAlias() !== $event->command) {
                return null;
            }
        }
        return $task;
    }

    protected function getSubscribedEvents(): array
    {
        // ScheduledTaskStarting::class;
        // ScheduledTaskFinished::class;
        return [
            // TaskStarting::class,
            // TaskFinished::class,
            CommandStarting::class,
            CommandFinished::class,
            TaskFailed::class,
        ];
    }
}
