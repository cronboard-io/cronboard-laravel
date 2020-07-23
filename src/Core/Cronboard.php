<?php

namespace Cronboard\Core;

use Closure;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Concerns\Boot;
use Cronboard\Core\Concerns\Exceptions;
use Cronboard\Core\Configuration;
use Cronboard\Core\Context\EventContext;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Exception as InternalException;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Support\Signing\Verifier;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskKey;
use Cronboard\Tasks\TaskRuntime;
use Exception;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Collection;

class Cronboard
{
    const VERSION = '0.6.0';

    use Boot;
    use Exceptions;

    protected $client;
    protected $config;
    protected $tasks;

    public function __construct(Container $app, Configuration $config)
    {
        $this->app = $app;
        $this->client = $app->make(Client::class);
        $this->config = $config;
        $this->tasks = new Collection;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    public function loadConfiguration(Configuration $config)
    {
        $this->config = $config;
    }

    protected function getConfiguration(): Configuration
    {
        return $this->config;
    }

    public function loadSnapshot(Snapshot $snapshot)
    {
        $this->tasks = $snapshot->getTasks()->keyBy(function($task){
            return $task->getKey();
        });
        return $this;
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function getTaskByKey(string $key = null): ?Task
    {
        if (! $key) return null;
        return $this->getTasks()->get($key);
    }

    public function updateToken(string $token)
    {
        $this->config->updateToken($token);
        $this->app->make(Client::class)->setToken($token);
        $this->app->make(Verifier::class)->setToken($token);
    }

    public function getTaskForEvent(Event $event): ?Task
    {
        $eventTask = $this->getTaskByKey($event->getTaskKey());
        $contextTask = TaskContext::getTask();

        if (! empty($eventTask) && ! empty($contextTask)) {
            if ($contextTask->hasSameBaseTaskAs($eventTask)) {
                return $contextTask;
            }
            return $eventTask;
        }

        return $eventTask ?: $contextTask;
    }

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

        $event->when(function() use ($event) {
            $task = $this->getTaskForEvent($event);

            $runtime = $this->getTrackedTaskRuntime($task);
            $output = $this->getConsoleOutput();

            if ($runtime) {
                $isDisabled = ! $runtime->isActive();
                $isUnknown = ! $task->getCommand()->exists();

                if ($isDisabled || $isUnknown) {
                    if ($isDisabled) {
                        $output->disabled('Scheduled command is disabled and will not run: ' . $event->getSummaryForDisplay());
                    }
                    return false;
                }
            }

            if (empty($runtime)) {
                $output->silent('Scheduled command is not supported or has not been recorded, and will not report to Cronboard: ' . $event->getSummaryForDisplay());
                $output->comment('Please run `cronboard:record` if you haven\'t or use a unique `name()` or `description()` to facilitate integration with Cronboard.');
            }

            if (! empty($runtime) && ! $runtime->isTracked()) {
                $output->silent('Scheduled command is silenced and will not report to Cronboard: ' . $event->getSummaryForDisplay());
            }

            return true;
        });

        $event->before(function(Dispatcher $dispatcher) use ($event) {
            EventContext::enter($event);

            $task = $this->getTaskForEvent($event);

            if ($task) {
                $runtime = TaskContext::enter($task);

                $queuedTask = $this->queue($task);
                $taskInstance = $queuedTask ?: $task;

                $event->linkWithTask($taskInstance);

                $dispatcher->dispatch(new TaskStarting($taskInstance));
            }

            EventContext::exit();
        });

        $event->after(function(Dispatcher $dispatcher) use ($event) {
            EventContext::enter($event);

            $task = $this->getTaskForEvent($event);

            if ($task) {
                $dispatcher->dispatch(new TaskFinished($task));
            }

            EventContext::exit();
        });

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

    /// REFACTOR BELOW
    public function fail(Task $task, Exception $exception = null): bool
    {
        if ($this->isOffline()) {
            return false;
        }

        if ($context = $this->getTrackedTaskRuntime($task)) {
            if ($exception) {
                $context->setException($exception);
            }
            $response = $this->client->tasks()->fail($task, $context);
            return $response['success'] ?? false;
        }

        return false;
    }

    public function start(Task $task): bool
    {
        if ($this->isOffline()) {
            return false;
        }

        if ($context = $this->getTrackedTaskRuntime($task)) {
            $response = $this->client->tasks()->start($task, $context);
            $success = $response['success'] ?? false;

            if ($success && ($key = $response['key'] ?? null)) {
                $this->switchToTaskInstance($task, $key);
            }

            return $success;
        }

        return false;
    }

    public function end(Task $task): bool
    {
        if ($this->isOffline()) {
            return false;
        }

        if ($context = $this->getTrackedTaskRuntime($task)) {
            $response = $this->client->tasks()->end($task, $context);
            return $response['success'] ?? false;
        }

        return false;
    }

    public function queue(Task $task): ?Task
    {
        if ($this->isOffline()) {
            return null;
        }

        return $this->catchInternalExceptionsInCallback(function() use ($task) {
            if ($context = $this->getTrackedTaskRuntime($task)) {
                $response = $this->client->tasks()->queue($task, $context);

                if ($responseKey = $response['key'] ?? false) {
                    // add queue task to current task list, so that it can be picked up if
                    // we're executing jobs using the sync driver
                    $queuedTask = $task->aliasAsRuntimeInstance($responseKey);

                    return $this->switchToTaskInstance($task, $responseKey, $queuedTask);
                }

                return $task;
            }
            return null;
        });
    }

    public function sendTaskOutput(Task $task, string $output)
    {
        return $this->catchInternalExceptionsInCallback(function() use ($task, $output) {
            if ($runtime = $this->getTrackedTaskRuntime($task)) {
                $response = $this->client->tasks()->output($task, $output);
                return $response['success'] ?? false;
            }
            return false;
        });
    }

    private function switchToTaskInstance(Task $originalTask, string $key, Task $taskInstance = null): Task
    {
        $isDifferentTask = $originalTask->getKey() !== $key;

        if ($isDifferentTask) {
            if (is_null($taskInstance)) {
                $taskInstance = $originalTask->aliasAsRuntimeInstance($key);
            }

            if (! $this->tasks->has($key)) {
                $this->tasks[$key] = $taskInstance;
            }
        }

        $taskInstance = $taskInstance ?: $originalTask;


        if ($isDifferentTask) {
            // switch context to the queue task as it may start execution immediately
            TaskContext::enter($taskInstance);
        }

        return $taskInstance;
    }

    private function catchInternalExceptionsInCallback(Closure $callback)
    {
        try {
            return $callback();
        } catch (InternalException $e) {
            $this->reportException($e);
        }
    }

    private function getTrackedTaskRuntime(Task $task = null): ?TaskRuntime
    {
        if (! empty($task)) {
            $runtime = TaskRuntime::fromTask($task);
            if ($runtime->isTracked()) {
                return $runtime;
            }
        }
        return null;
    }
}
