<?php

namespace Cronboard\Core\Execution\Listeners;

use Cronboard\Core\Api\Exception;
use Cronboard\Core\Cronboard;
use Cronboard\Tasks\Task;
use Log;
use ReflectionClass;
use Exception as BaseException;

abstract class EventSubscriber
{
    protected $cronboard;

    public function __construct(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;
    }

    public function log($event)
    {
        $details = [];
        if (isset($event->command)) {
            $details['command'] = $event->command;
        }

        $task = null;
        try {
            $task = $this->getTaskFromEvent($event);
        } catch (\Exception $e) {}

        if (empty($task) && isset($event->task)) {
            $task = $event->task;
        }

        if ($task) {
            $details = array_merge($details, [
                'task' => $task->getKey(),
                'command' => $task->getCommand()->getHandler(),
                'commandType' => $task->getCommand()->getType(),
            ]);
        }

        Log::info('Task Event:  ' . get_class($event), $details);
    }

    abstract protected function getSubscribedEvents(): array;

    protected function getTaskFromEvent($event): ?Task
    {
        $context = $this->cronboard->getContext();
        if ($context && ($taskKey = $context->getTask())) {
            return $this->cronboard->getTaskByKey($taskKey);
        }
        return null;
    }

    protected function isTaskSupported(Task $task): bool
    {
        return true;
    }

    protected function failTask(Task $task = null, BaseException $exception = null)
    {
        if (empty($task)) return;
        if (!$this->isTaskSupported($task)) return;
        try {
            if ($context = $this->enterTaskContext($task)) {
                $this->cronboard->fail($task, $exception);
            }
        } catch (Exception $e) {
            $this->cronboard->reportException($e);
        } finally {
            $this->exitTaskContext($task);
        }
    }

    protected function startTask(Task $task = null)
    {
        if (empty($task)) return;
        if (!$this->isTaskSupported($task)) return;
        try {
            if ($context = $this->enterTaskContext($task)) {
                $this->cronboard->start($task);
            }
        } catch (Exception $e) {
            $this->cronboard->reportException($e);
            $this->exitTaskContext($task);
        }
    }

    protected function endTask(Task $task = null, array $data = [])
    {
        if (empty($task)) return;
        if (!$this->isTaskSupported($task)) return;

        try {
            if ($context = $this->setTaskContext($task)) {
                $context->finalise();
                $this->cronboard->end($task);
            }
        } catch (Exception $e) {
            $this->cronboard->reportException($e);
        } finally {
            $this->exitTaskContext($task);
        }
    }

    protected function enterTaskContext(Task $task)
    {
        // if current subscriber does not support this type of task
        if (!$this->isTaskSupported($task)) return;

        // try to get existing context because it may have been set already
        // for tasks that run in the same process as the schedule:run
        // command
        $context = $this->cronboard->getContext();

        if (empty($context)) {
            $context = $this->setTaskContext($task);
        }

        if (!empty($context)) {
            $context->enter();
        }

        return $context;
    }

    protected function exitTaskContext(Task $task)
    {
        $context = $this->setTaskContext($task);
        if (!empty($context)) {
            // remove context from cronboard instance
            $this->setTaskContext(null);
            // trigger exit callbacks
            $context->exit();
        }
        return $context;
    }

    protected function setTaskContext(Task $task = null)
    {
        $taskContext = $this->cronboard->setTaskContextWhenTracked($task);

        if (empty($taskContext)) {
            return null;
        }

        return $taskContext;
    }

    public function subscribe($events)
    {
        foreach ($this->getSubscribedEvents() as $eventClass) {

            $shortName = (new ReflectionClass($eventClass))->getShortName();
            $specialisedMethodName = 'handle' . $shortName;

            if (method_exists($this, $specialisedMethodName)) {
                $events->listen($eventClass, static::class . '@' . $specialisedMethodName);
            } else if (method_exists($this, 'handle')) {
                $events->listen($eventClass, static::class . '@handle');
            }
        }
    }
}
