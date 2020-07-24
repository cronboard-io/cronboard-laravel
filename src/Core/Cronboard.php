<?php

namespace Cronboard\Core;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Concerns\Boot;
use Cronboard\Core\Concerns\Exceptions;
use Cronboard\Core\Concerns\Requests;
use Cronboard\Core\Concerns\Tracking;
use Cronboard\Core\Configuration;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Support\Signing\Verifier;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class Cronboard
{
    const VERSION = '0.6.0';

    use Boot;
    use Exceptions;
    use Requests;
    use Tracking;

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

    public function getClient(): Client
    {
        return $this->client;
    }

    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    protected function getApplication(): Container
    {
        return $this->app;
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

    protected function registerNewTask(string $key, Task $task)
    {
        $this->tasks[$key] = $task;
    }

    public function getTaskByKey(string $key = null): ?Task
    {
        if (! $key) return null;
        return $this->getTasks()->get($key);
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

    public function updateToken(string $token)
    {
        $this->config->updateToken($token);
        $this->app->make(Client::class)->setToken($token);
        $this->app->make(Verifier::class)->setToken($token);
    }
}
