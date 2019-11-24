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
        $this->ensureHasBooted();
        return $this->context;
    }

    public function setTaskContext(Task $task = null): ?TaskContext
    {
        if (! empty($task)) {
            return $this->context = TaskContext::fromTask($this->app, $task);
        }
        return $this->context = null;
    }

    public function setTaskContextWhenTracked(Task $task = null): ?TaskContext
    {
        if ($context = $this->setTaskContext($task)) {
            if (! $context->isTracked()) {
                $context->exit();
                $this->setTaskContext($context = null);
            }
        }
        return $context;
    }

    public function getTrackedContextForTask(Task $task): ?TaskContext
    {
        return $this->getContext() ?: $this->setTaskContextWhenTracked($task);
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
