<?php

namespace Cronboard\Core\Execution\Listeners;

use Cronboard\Core\Execution\Events\TaskFailed;
use Cronboard\Tasks\Task;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;

class JobEventSubscriber extends EventSubscriber
{
    public function handleJobExceptionOccurred(JobExceptionOccurred $event)
    {
        // perhaps we should not track this as it may fire multiple times for each retry?
        // and we should instead use the job failed event
        // $this->failTask($this->getTaskFromEvent($event));
    }

    public function handleTaskFailed(TaskFailed $event)
    {
        $this->failTask($this->getJobTaskFromTaskEvent($event));
    }

    protected function getJobTaskFromTaskEvent($event): ?Task
    {
        $task = $event->task;
        if ($task->getCommand()->isJobCommand()) {
            return $task;
        }
        return null;
    }

    public function handleJobFailed(JobFailed $event)
    {
        $this->failTask($this->getTaskFromEvent($event), $event->exception);
    }

    public function handleJobProcessed(JobProcessed $event)
    {
        $this->endTask($this->getTaskFromEvent($event));
    }

    public function handleJobProcessing(JobProcessing $event)
    {
        $this->startTask($this->getTaskFromEvent($event));
    }

    protected function getTaskFromEvent($event): ?Task
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
            JobExceptionOccurred::class,
            JobFailed::class,
            JobProcessed::class,
            JobProcessing::class,
            TaskFailed::class,
        ];
    }
}
