<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Core\Context\EventContext;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskRuntime;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;

trait Tracking
{
    abstract protected function getConsoleOutput();
    abstract public function getTaskForEvent(Event $event): ?Task;
    abstract public function queue(Task $task): ?Task;

    public function trackEvent(Event $event)
    {
        if ($event->isTracked()) {
            return;
        }

        $task = $this->getTaskForEvent($event);
        if ($task) {
            $runtime = TaskRuntime::fromTask($task);
            $event->adjustRuntimeData($runtime);
        }

        $this->attachConditionalTaskStartChecks($event);

        $this->attachBeforeTaskStartActions($event);

        $this->attachAfterTaskEndActions($event);

        $event->setTracked();
    }

    public function trackSchedule(Schedule $schedule)
    {
        foreach ($schedule->events() as $event) {
            if ($event->shouldTrack()) {
                $this->trackEvent($event);
            }
        }
    }

    protected function getTrackedTaskRuntime(Task $task = null): ?TaskRuntime
    {
        if (! empty($task)) {
            $runtime = TaskRuntime::fromTask($task);
            if ($runtime->isTracked()) {
                return $runtime;
            }
        }
        return null;
    }

    private function attachConditionalTaskStartChecks(Event $event)
    {
        $event->when(function() use ($event) {
            $task = $this->getTaskForEvent($event);

            $runtime = $this->getTrackedTaskRuntime($task);
            $output = $this->getConsoleOutput();

            if ($runtime) {
                $isDisabled = ! $runtime->isActive();
                $isUnknown = ! $task->getCommand()->exists();

                if ($isDisabled) {
                    $output->disabled('Scheduled command is disabled and will not run: ' . $event->getSummaryForDisplay());
                }

                if ($isDisabled || $isUnknown) {
                    return false;
                }
            }

            if (empty($runtime)) {
                $output->silent('Scheduled command is not supported or has not been recorded, and will not report to Cronboard: ' . $event->getSummaryForDisplay());
                $output->comment('Please run `cronboard:record` if you haven\'t or use a unique `name()` or `description()` to facilitate integration with Cronboard.');
            } else if (! $runtime->isTracked()) {
                $output->silent('Scheduled command is silenced and will not report to Cronboard: ' . $event->getSummaryForDisplay());
            }

            return true;
        });
    }

    private function attachBeforeTaskStartActions(Event $event)
    {
        $event->before(function(Dispatcher $dispatcher) use ($event) {
            EventContext::enter($event);

            $task = $this->getTaskForEvent($event);

            if ($task) {
                TaskContext::enter($task);

                $queuedTask = $this->queue($task);
                $taskInstance = $queuedTask ?: $task;

                $event->linkWithTask($taskInstance);

                $dispatcher->dispatch(new TaskStarting($taskInstance));
            }

            EventContext::exit();
        });
    }

    private function attachAfterTaskEndActions(Event $event)
    {
        $event->after(function(Dispatcher $dispatcher) use ($event) {
            EventContext::enter($event);

            $task = $this->getTaskForEvent($event);

            if ($task) {
                $dispatcher->dispatch(new TaskFinished($task));
            }

            EventContext::exit();
        });
    }
}
