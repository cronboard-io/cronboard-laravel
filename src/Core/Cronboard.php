<?php

namespace Cronboard\Core;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Api\Endpoints\Tasks;
use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Exceptions\Exception as InternalException;
use Cronboard\Support\Signing\Verifier;
use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskContext;
use Cronboard\Tasks\TaskKey;
use Exception;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Closure;

class Cronboard
{
    use Concerns\Boot;
    use Concerns\Context;
    use Concerns\Exceptions;
    use Concerns\Output;
    use Concerns\Tracking;

    protected $tasks;
    protected $commands;

	public function __construct(Container $app, Configuration $config)
	{
        $this->app = $app;
		$this->config = $config;

        $this->commands = new Collection;
        $this->tasks = new Collection;

        $this->exceptionListeners = new Collection;
	}

    public function loadSnapshot(Snapshot $snapshot)
    {
        $this->commands = $snapshot->getCommands();
        $this->tasks = $snapshot->getTasks()->keyBy->getKey();
    }

    public function updateToken(string $token)
    {
        $this->config->updateToken($token);
        $this->app->make(Client::class)->setToken($token);
        $this->app->make(Verifier::class)->setToken($token);
    }

    public function getTaskForEvent(Event $event): ?Task
    {
        $resolver = new Resolver($this);

        if ($task = $resolver->resolveFromEventTask($event)) {
            return $task;
        }

        return $resolver->resolveFromEvent($event) ?: $resolver->resolveFromEnvironment();
    }

    public function getTaskByKey(string $key): ?Task
    {
        $this->ensureHasBooted();
        return $this->getTasks()->get($key);
    }

    public function getTasks(): Collection
    {
        return $this->tasks;
    }

    public function fail(Task $task, Exception $exception = null): bool
    {
        if ($context = $this->getTrackedContextForTaskForRequest($task)) {
            if ($exception) {
                $context->setException($exception);
            }
            $response = $this->app->make(Tasks::class)->fail($task, $context);
            return $response['success'] ?? false;
        }
        return false;
    }

    public function start(Task $task): bool
    {
        if ($context = $this->getTrackedContextForTaskForRequest($task)) {
            $response = $this->app->make(Tasks::class)->start($task, $context);
            return $response['success'] ?? false;
        }
        return false;
    }

    public function end(Task $task): bool
    {
        if ($context = $this->getTrackedContextForTaskForRequest($task)) {
            $response = $this->app->make(Tasks::class)->end($task, $context);
            return $response['success'] ?? false;
        }
        return false;
    }

    public function queue(Task $task)
    {
        return $this->catchInternalExceptionsInCallback(function() use ($task) {
            if ($context = $this->getTrackedContextForTaskForRequest($task)) {
                $response = $this->app->make(Tasks::class)->queue($task, $context);
                $responseKey = $response['key'] ?? false;

                if ($responseKey) {
                    // add queue task to current task list, so that it can be picked up if
                    // we're executing jobs using the sync driver
                    $queuedTask = $task->aliasAsCustomTask($responseKey);
                    $this->tasks[$responseKey] = $queuedTask;

                    // switch context to the queue task as it may start execution immediately
                    $this->setTaskContext($queuedTask);
                }

                return $responseKey;
            }
            return false;
        });
    }

    public function sendTaskOutput(Task $task, string $output)
    {
        return $this->catchInternalExceptionsInCallback(function() use ($task, $output) {
            if ($context = $this->getTrackedContextForTaskForRequest($task)) {
                $response = $this->app->make(Tasks::class)->output($task, $output);
                return $response['success'] ?? false;
            }
            return false;
        });
    }

    private function catchInternalExceptionsInCallback(Closure $callback)
    {
        try {
            return $callback();
        } catch (InternalException $e) {
            $this->reportException($e);
        }
    }

    private function getTrackedContextForTaskForRequest(Task $task): ?TaskContext
    {
        // we still need to load the context, even if we're not returning it
        // that way we make sure the no requests are made, but we still
        // enter the context
        $context = $this->getTrackedContextForTask($task);
        return $this->isOffline() ? null : $context;
    }
}
