<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskContext;
use Illuminate\Console\Scheduling\Event;

trait Context
{
    protected $context;

    public function setTaskContextByKey(string $key)
    {
        return $this->setTaskContext($this->getTaskByKey($key));
    }

    public function getContext(): ?TaskContext
    {
        return $this->context;
    }

    public function getTaskContextByKey(string $key): ?TaskContext
    {
        return TaskContext::fromTaskKey($this->app, $key);;
    }

    public function setTaskContext(Task $task = null): ?TaskContext
    {
        if (!empty($task)) {
            return $this->context = TaskContext::fromTask($this->app, $task);
        }
        return $this->context = null;
    }

    public function setTaskContextWhenTracked(Task $task = null): ?TaskContext
    {
        if ($context = $this->setTaskContext($task)) {
            return $this->forwardTrackedContext($context);
        }
        return null;
    }

    public function getTrackedContextForTask(Task $task): ?TaskContext
    {
        if ($context = $this->getContext()) {
            return $this->forwardTrackedContext($context);
        }
        return $this->setTaskContextWhenTracked($task);
    }

    private function forwardTrackedContext(TaskContext $context): ?TaskContext
    {
        if (! $context->isTracked()) {
            $context->exitAndKeep();
            return $this->setTaskContext(null);
        }
        return $context;
    }

    protected function loadCurrentTaskContextFromEnvironment(): ?TaskContext
    {
        $resolver = new Resolver($this);
        if ($task = $resolver->resolveFromEnvironment()) {
            return $this->setTaskContext($task);
        }
        return null;
    }
}
