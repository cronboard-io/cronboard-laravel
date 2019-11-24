<?php

namespace Cronboard\Tasks;

use Cronboard\Core\Cronboard;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskKey;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Env;

class Resolver
{
    const TASK_KEY_ENV_VAR = 'CRONBOARD_TASK';

    protected $cronboard;

    public function __construct(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;
    }

    public function resolveFromContext(): ?Task
    {
        if ($context = $this->cronboard->getContext()) {
            return $this->getTask($context->getTask());
        }
        return null;
    }

    public function resolveFromEnvironment(): ?Task
    {
        $taskKey = $this->getTaskKeyEnvHelper() ?: ($this->getTaskKeyFromEnvArray() ?: $this->getTaskKeyFromServerArray());
        return $this->getTask($taskKey);
    }

    private function getTaskKeyEnvHelper(): ?string
    {
        if (class_exists(Env::class)) {
            return Env::get(static::TASK_KEY_ENV_VAR);
        } else if (function_exists('env')) {
            return env(static::TASK_KEY_ENV_VAR);
        }
    }

    private function getTaskKeyFromEnvArray(): ?string
    {
        return $_ENV[static::TASK_KEY_ENV_VAR] ?? null;
    }

    private function getTaskKeyFromServerArray(): ?string
    {
        return $_SERVER[static::TASK_KEY_ENV_VAR] ?? null;
    }

    public function resolveFromEvent(Event $event): ?Task
    {
        return $this->getTask(TaskKey::createFromEvent($event));
    }

    public function resolveFromEventTask($event): ?Task
    {
        if (method_exists($event, 'getTask')) {
            return $event->getTask();
        }
    }

    protected function getTask(string $key = null)
    {
        // dump('booted: ' . ($this->cronboard->booted() ? 'yes' : 'no'), $this->cronboard->getTasks()->map->getKey());
        if (empty($key)) return null;
        return $this->cronboard->getTaskByKey($key);
    }}
