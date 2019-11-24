<?php

namespace Cronboard\Core\Execution\Listeners;

use Cronboard\Core\Execution\Events\TaskFailed;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Tasks\Task;
use Illuminate\Support\Collection;

class CallableEventSubscriber extends EventSubscriber
{
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
        $command = $task->getCommand();
        return $command->isClosureCommand() || $command->isInvokableCommand();
    }

    protected function enterTaskContext(Task $task)
    {
        $taskRequiresTrackingLabels = $task->getCommand()->isClosureCommand();
        $taskHasTrackingLabels = $this->taskContainsTrackingLabels($task);

        if ($taskRequiresTrackingLabels && ! $taskHasTrackingLabels) {
            return null;
        }

        return parent::enterTaskContext($task);
    }

    protected function taskContainsTrackingLabels(Task $task): bool
    {
        $trackingLabels = ['name', 'description'];
        return ! Collection::wrap($task->getConstraints())->map(function($constraint){
            return $constraint[0];
        })->intersect($trackingLabels)->isEmpty();
    }

    protected function getTaskFromEvent($event): ?Task
    {
        return $event->task;
    }

    protected function getSubscribedEvents(): array
    {
        return [
            TaskStarting::class,
            TaskFinished::class,
            TaskFailed::class
        ];
    }
}