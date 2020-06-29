<?php

namespace Cronboard\Core;

use Cronboard\Core\Api\Endpoints\Cronboard;
use Cronboard\Core\Api\Exception;
use Cronboard\Core\Discovery\HandlesSnapshotStorage;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Reflection\ParseParameters;
use Cronboard\Support\Environment;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskContext;
use Illuminate\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Load remote task data from Cronboard service and create tasks for them.
 * They are then stored locally as part of the snapshot.
 */
class ExtendSnapshotWithRemoteTasks
{
    use HandlesSnapshotStorage;

    protected $app;
    protected $cronboard;

    public function __construct(Container $app)
    {
        $this->app = $app;
        $this->cronboard = $app['cronboard'];
    }

    public function execute(Snapshot $snapshot)
    {
        try {
            $environmentInfo = (new Environment($this->app))->toArray();
            $environment = Arr::get($environmentInfo, 'environment');

            // get remote tasks
            $response = $this->app->make(Cronboard::class)->cronboard($environment);
            $scheduleTasksPayload = Collection::wrap($response['tasks']);
            $tasksPayload = $scheduleTasksPayload->merge($response['queuedTasks']);

            // get local and remote task definitions
            $remoteScheduleTasks = $this->createTasksFromPayload($scheduleTasksPayload, $snapshot);
            $localScheduleTasks = $snapshot->getTasks();

            // get task aliases
            $scheduleTasks = $localScheduleTasks->merge($remoteScheduleTasks->keyBy->getKey());
            $taskAliases = $response['aliases'] ?? [];
            $remoteTaskAliases = $this->createTasksFromTaskAliases($scheduleTasks, $taskAliases, $tasksPayload);

            // add remote tasks to snapshot
            $remoteTasks = $remoteScheduleTasks->merge($remoteTaskAliases);
            $snapshot->addRemoteTasks($remoteTasks);

            $this->storeSnapshot($snapshot);

            // load contexts for all tasks and store locally
            $this->loadTaskContext($tasksPayload, $taskAliases);

        } catch (Exception $e) {
            // report exception and return snapshot untouched
            $this->cronboard->reportException($e);
        }

        return $snapshot;
    }

    protected function createTasksFromTaskAliases(Collection $availableTasks, array $aliases, Collection $tasksPayload)
    {
        if (! empty($aliases)) {
            $tasksPayloadByKey = $tasksPayload->keyBy('key');
            $availableTasksByKey = $availableTasks->keyBy->getKey();

            $aliasedTasks = Collection::wrap($aliases)->map(function($taskKey, $aliasedKey) use ($availableTasksByKey, $tasksPayloadByKey) {
                $task = $availableTasksByKey->get($taskKey);
                if (! empty($task)) {
                    $taskData = $tasksPayloadByKey[$aliasedKey] ?? ($tasksPayloadByKey[$taskKey] ?? null);

                    $parameters = (new ParseParameters)->execute($taskData['parameters']);
                    $aliasedTask = $task->aliasAsRuntimeInstance($aliasedKey, $taskData['once'] ?? false, $parameters);

                    return $aliasedTask;
                }
                return null;
            })->filter()->values();

            if (!$aliasedTasks->isEmpty()) {
                return $aliasedTasks;
            }
        }
        return new Collection;
    }

    protected function loadTaskContext(Collection $tasksPayload, array $taskAliases)
    {
        $contextStorage = TaskContext::getStorage($this->app);
        $contextStorage->empty();

        $tasksPayloadByKey = $tasksPayload->keyBy('key');

        $defaultContext = [
            'silent' => 0,
            'active' => 1,
            'once' => 0,
        ];
        foreach ($tasksPayloadByKey as $taskKey => $taskData) {
            $taskData = array_merge($defaultContext, $taskData);

            $context = new TaskContext($this->app, $taskKey);
            $context->setTracking(!$taskData['silent']);
            $context->setActive($taskData['active']);
            $context->setOnce($taskData['once']);
            $context->setOverrides($taskData['overrides'] ?? []);
        }

        foreach ($taskAliases as $aliasedTaskKey => $taskKey) {
            TaskContext::inheritTaskContext($this->app, $aliasedTaskKey, $taskKey);
        }
    }

    protected function createTasksFromPayload(Collection $tasksPayload, Snapshot $snapshot): Collection
    {
        $tasks = Collection::wrap($tasksPayload)
            ->map(function($taskData, $taskKey) use ($snapshot) {
                return $this->createTaskFromPayload($taskData, $snapshot);
            })
            ->filter();

        return $tasks;
    }

    protected function createTaskFromPayload(array $taskData, Snapshot $snapshot): ?Task
    {
        $command = $snapshot->getCommandByKey($taskData['command']['key']);
        if (empty($command)) {
            return null;
        }

        $parameters = (new ParseParameters)->execute($taskData['parameters']);
        $task = new Task($taskData['key'], $command, $parameters, $taskData['constraints']);
        $task->setDetails(Arr::only($taskData, 'name'));

        $task->setCronboardTask(true);
        $task->setSingleExecution($taskData['once']);

        return $task;
    }
}
