<?php

namespace Cronboard\Core\Concerns;

use Closure;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Exception as InternalException;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskRuntime;
use Exception;

trait Requests
{
    abstract protected function getTrackedTaskRuntime(Task $task = null): ?TaskRuntime;
    abstract protected function isOffline(): bool;
    abstract public function getClient(): Client;
    abstract public function reportException(Exception $exception);
    abstract public function getTaskByKey(string $key = null): ?Task;
    abstract protected function registerNewTask(string $key, Task $task);

    public function fail(Task $task, Exception $exception = null): bool
    {
        if ($this->isOffline()) {
            return false;
        }

        if ($runtime = $this->getTrackedTaskRuntime($task)) {
            if ($exception) {
                $runtime->setException($exception);
            }
            $response = $this->getClient()->tasks()->fail($task, $runtime);
            return $response['success'] ?? false;
        }

        return false;
    }

    public function start(Task $task): bool
    {
        if ($this->isOffline()) {
            return false;
        }

        if ($runtime = $this->getTrackedTaskRuntime($task)) {
            $response = $this->getClient()->tasks()->start($task, $runtime);
            $success = $response['success'] ?? false;

            if ($success) {
                if ($key = $response['key'] ?? null) {
                    $this->switchToTaskInstance($task, $key);    
                }
            } else {
                $runtime->stopTracking();
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

        if ($runtime = $this->getTrackedTaskRuntime($task)) {
            $response = $this->getClient()->tasks()->end($task, $runtime);
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
            if ($runtime = $this->getTrackedTaskRuntime($task)) {
                $response = $this->getClient()->tasks()->queue($task, $runtime);
                $success = $response['success'] ?? false;

                if ($success) {
                    if ($responseKey = $response['key'] ?? false) {
                        // add queue task to current task list, so that it can be picked up if
                        // we're executing jobs using the sync driver
                        $queuedTask = $task->aliasAsRuntimeInstance($responseKey);

                        return $this->switchToTaskInstance($task, $responseKey, $queuedTask);
                    }
                } else {
                    $runtime->stopTracking();
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
                $response = $this->getClient()->tasks()->output($task, $output);
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

            if (is_null($this->getTaskByKey($key))) {
                $this->registerNewTask($key, $taskInstance);
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
}
