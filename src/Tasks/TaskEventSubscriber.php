<?php

namespace Cronboard\Tasks;

use Cronboard\Core\Api\Exception;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Execution\Events\TaskFailed;
use Cronboard\Core\Execution\Events\TaskFinished;
use Cronboard\Core\Execution\Events\TaskStarting;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Support\Helpers;
use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\Task;
use Throwable;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Contracts\Queue\Job;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\SyncQueue;
use Illuminate\Support\Arr;

class TaskEventSubscriber
{
    protected $cronboard;

    public function __construct(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;
    }

    public function handleCommandTask($event)
    {
    	$task = TaskContext::getTask() ?: $this->getTaskFromEnvironment();

    	if ($task) {
    		if (! $task->getCommand()->isConsoleCommand()) {
    			return;
    		}

            if ($task->getCommand()->getAlias() !== $event->command) {
                return;
            }

    		if ($event instanceof CommandStarting) {
	    		$this->startTask($task);
	    	} else if ($event instanceof CommandFinished) {
	    		$this->endTask($task, ['exitCode' => $event->exitCode]);
	    	}
    	}
    }

    public function handleCallableTask($event)
    {
    	$task = $event->task;
    	$command = $task->getCommand();
    	$isSupported = $command->isClosureCommand() || $command->isInvokableCommand() || $command->isExecCommand();

        if (! $isSupported && $command->isJobCommand()) {
            $shouldQueue = Helpers::implementsInterface($command->getHandler(), ShouldQueue::class);
            $isSupported = ! $shouldQueue;// || $this->isJobTaskUsingSyncQueue($task);
        }

		if ($isSupported) {
			if ($event instanceof TaskStarting) {
	    		$this->startTask($task);
	    	} else if ($event instanceof TaskFinished) {
	    		$this->endTask($task);
	    	}
		}
    }

    private function isJobTaskUsingSyncQueue(Task $task): bool
    {
        $container = Container::getInstance();
        $parameters = $task->getParameters()->getGroupParameters(Parameters::GROUP_SCHEDULE);

        $scheduleConnection = optional($parameters->getParameterByName('connection'))->getValue();
        $jobConnection = $container->make($task->getCommand()->getHandler())->connection;
        $connection = $scheduleConnection ?: $jobConnection;

        $connection = $container[QueueFactory::class]->connection($connection);

        return $connection instanceof SyncQueue;
    }

    public function handleAnyTaskFailure(TaskFailed $event)
    {
    	$this->failTask($event->task, $event->exception);
    }

    public function handleJobTaskFromQueue($event)
    {
    	$task = $this->getTaskFromJob($event->job);

    	if ($task) {
    		if ($event instanceof JobFailed) {
    			$this->failTask($task, $event->exception);
    		} else if ($event instanceof JobProcessed) {
    			$this->endTask($task);
    		} else if ($event instanceof JobProcessing) {
    			$this->startTask($task);
    		}
    	}
    }

    protected function startTask(Task $task)
    {
        try {
        	$this->enterTaskContext($task);
        	$this->cronboard->start($task);
        } catch (Exception $e) {
            $this->cronboard->reportException($e);
            $this->exitTaskContext($task);
        }
    }

    protected function endTask(Task $task, array $data = [])
    {
        try {
        	if (! $task->hasFailed()) {
        		$runtime = $this->enterTaskContext($task);
                $runtime->finalise();

                $this->cronboard->end($task);
            }
        } catch (Exception $e) {
            $this->cronboard->reportException($e);
        } finally {
            $this->exitTaskContext($task);
        }
    }

    protected function failTask(Task $task, Throwable $exception)
    {
        try {
        	$task->setFailed();

        	$runtime = $this->enterTaskContext($task);
            $runtime->finalise();

            $this->cronboard->fail($task, $exception);
        } catch (Exception $e) {
            $this->cronboard->reportException($e);
        } finally {
            $this->exitTaskContext($task);
        }
    }

    private function enterTaskContext(Task $task): TaskRuntime
    {
        return TaskContext::enter($task);
    }

    private function exitTaskContext(Task $task)
    {
        TaskContext::exit();
    }

    private function getTaskFromEnvironment(): ?Task
    {
    	return $this->cronboard->getTaskByKey(Resolver::resolveFromEnvironment());
    }

    private function getTaskFromJob(Job $job): ?Task
    {
        if ($key = Helpers::getTrackedJobTaskKey($job)) {
            return $this->cronboard->getTaskByKey($key);
        }
    	return null;
    }

    public function subscribe($events)
    {
    	$events->listen([
    		CommandStarting::class,
    		CommandFinished::class
    	], static::class . '@handleCommandTask');

    	$events->listen(TaskFailed::class, static::class . '@handleAnyTaskFailure');

    	$events->listen([
    		TaskStarting::class,
    		TaskFinished::class
    	], static::class . '@handleCallableTask');

    	$events->listen([
    		// JobExceptionOccurred::class,
            JobFailed::class,
            JobProcessed::class,
            JobProcessing::class,
    	], static::class . '@handleJobTaskFromQueue');
    }
}
