<?php

namespace Cronboard\Tasks\Events;

use Cronboard\Core\Cronboard;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskKey;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event as LaravelScheduleEvent;
use Illuminate\Contracts\Events\Dispatcher;

trait TrackedEvent
{
    protected $task;
    protected $cronboard;

    protected $recordingOutputInTask = false;

    public function setCronboard(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;
        return $this;
    }

    public function getCronboard(): ?Cronboard
    {
        return $this->cronboard;
    }

    public function getName(): ?string
    {
        return $this->description;
    }

    public function setTask(Task $task = null)
    {
        $this->task = $task;
        $this->recordOutputInTask();
        return $this;
    }

    public function linkToCronboard(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;

        $this->before(function(Dispatcher $dispatcher) {
            $this->notifyTaskStarting($dispatcher);
        });

        $isCallbackEvent = $this instanceof CallbackEvent;
        if (! $isCallbackEvent) {
            $this->after(function(Dispatcher $dispatcher) {
                $this->notifyTaskFinished($dispatcher);
            });
        }

        return $this;
    }

    protected function notifyTaskStarting(Dispatcher $dispatcher)
    {
        $task = $this->loadTaskFromCronboard();
        // dump('notifyTaskStarting' . (empty($task) ? 'missing' : 'found') . ' ' . getmypid());
        if ($task) {
            $dispatcher->dispatch(new TaskStarting($task));
        }
    }

    protected function notifyTaskFinished(Dispatcher $dispatcher)
    {
        $task = $this->loadTaskFromCronboard();
        if ($task) {
            $dispatcher->dispatch(new TaskFinished($task));
        }
    }

    public function loadTaskFromCronboard()
    {
        $task = $this->cronboard->getTaskForEvent($this);
        $this->setTask($task);
        return $task;
    }

    public function getTask(): ?Task
    {
        return $this->task;
    }

    public function recordOutputInTask()
    {
        if (method_exists($this, 'ensureOutputIsBeingCaptured')) {
            $this->ensureOutputIsBeingCaptured();  // Laravel 5.7+
        } else if (method_exists($this, 'ensureOutputIsBeingCapturedForEmail')) {
            $this->ensureOutputIsBeingCapturedForEmail(); // Laravel 5.6
        }

        if (! $this->recordingOutputInTask) {
            $this->recordingOutputInTask = true;

            $task = $this->task;
            $event = $this;

            $this->then(function (Cronboard $cronboard) use ($task, $event) {
                if (! empty($task)) {
                    $output = $this->getEventOutput($event);
                    if (! empty($output)) {
                        $cronboard->sendTaskOutput($task, $output);
                    }
                }
            });
        }
    }

    protected function getEventOutput(LaravelScheduleEvent $event)
    {
        if (! $event->output ||
            $event->output === $event->getDefaultOutput() ||
            $event->shouldAppendOutput ||
            ! file_exists($event->output)) {
            return '';
        }
        return trim(file_get_contents($event->output));
    }

    /**
     * Determine if the Cron expression passes.
     *
     * @return bool
     */
    protected function expressionPasses()
    {
        if ($this->shouldExecuteImmediately()) {
            return true;
        }
        return parent::expressionPasses();
    }

    /**
     * Determine if the filters pass for the event.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return bool
     */
    public function filtersPass($app)
    {
        $task = $this->cronboard->getTaskForEvent($this);
        $taskContext = $this->getCronboard()->setTaskContext($task);

        if ($output = $this->cronboard->getOutput()) {
            if (($taskContext && ! $taskContext->isActive())) {
                $output->disabled('Scheduled command is disabled and will not run: ' . $this->getSummaryForDisplay());
                return false;
            }

            if (empty($taskContext)) {
                $output->silent('Scheduled command is not supported or has not been recorded, and will not report to Cronboard: ' . $this->getSummaryForDisplay());
                $output->comment('Please run `cronboard:record` if you haven\'t or use a unique `name()` or `description()` to facilitate integration with Cronboard.');
            }

            if (! empty($taskContext) && ! $taskContext->isTracked()) {
                $output->silent('Scheduled command is silenced and will not report to Cronboard: ' . $this->getSummaryForDisplay());
            }
        }

        if ($this->shouldExecuteImmediately()) {
            return true;
        }

        return parent::filtersPass($app);
    }

    protected function shouldExecuteImmediately(): bool
    {
        if ($task = $this->getTask()) {
            $context = $this->getCronboard()->setTaskContext($task);
            if ($context->shouldExecuteImmediately()) {
                return true;
            }
        }
        return false;
    }
}
