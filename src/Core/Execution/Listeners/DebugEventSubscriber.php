<?php

namespace Cronboard\Core\Execution\Listeners;

use Cronboard\Core\Execution\Events\TaskFailed;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Tasks\Task;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;

class DebugEventSubscriber extends EventSubscriber
{
    public function handle($event)
    {
        $eventClass = get_class($event);
        $task = null;

        $isJobEvent = in_array($eventClass, [
            JobExceptionOccurred::class,
            JobFailed::class,
            JobProcessed::class,
            JobProcessing::class
        ]);

        $isCommandEvent = in_array($eventClass, [
            CommandStarting::class,
            CommandFinished::class,
        ]);

        if ($isJobEvent) {
            $task = $this->getJobTaskFromEvent($event);
        } else if ($isCommandEvent) {
            $task = $this->getCommandTaskFromEvent($event);
        } else {
            $task = $event->task;
        }

        $data = [
            'eventClass' => get_class($event),
            'taskKey' => ($task ? $task->getKey() : null),
            'artisanCommand' => ($event->command ?? null),
            'processId' => getmypid()
        ];

        $this->debug($data);
    }

    protected function debug($data)
    {
        dump($data);
    }

    protected function getCommandTaskFromEvent($event): ?Task
    {
        $task = $this->getTaskFromEvent($event);
        if (!empty($task) && $task->getCommand()->isConsoleCommand()) {
            if ($task->getCommand()->getAlias() !== $event->command) {
                return null;
            }
        }
        return $task;
    }

    protected function getJobTaskFromEvent($event): ?Task
    {
        $payload = $event->job->payload();
        if ($serializedJob = Arr::get($payload, 'data.command')) {
            $job = unserialize($serializedJob);
            if (method_exists($job, 'getTaskKey') && ($key = $job->getTaskKey())) {
                return $this->cronboard->getTaskByKey($key);
            }
        }
        return null;
    }

    protected function getSubscribedEvents(): array
    {
        return [
            TaskStarting::class,
            TaskFinished::class,
            TaskFailed::class,

            CommandStarting::class,
            CommandFinished::class,

            JobExceptionOccurred::class,
            JobFailed::class,
            JobProcessed::class,
            JobProcessing::class,
        ];
    }
}