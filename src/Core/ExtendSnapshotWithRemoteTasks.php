<?php

namespace Cronboard\Core;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Api\Exception;
use Cronboard\Core\Discovery\HandlesSnapshotStorage;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Reflection\ParseParameters;
use Cronboard\Support\Environment;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskRuntime;
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
            $cli = $environmentInfo['cli.command'] ?? null;

            // get remote tasks
            $response = $this->app->make(Client::class)
                ->cronboard()
                ->schedule($environment, $cli ?: '');

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
            $this->loadTaskRuntime($tasksPayload, $taskAliases);

        } catch (Exception $exception) {
            if ($exception->getStatusCode() === 401) {
                throw ConfigurationException::tokenNotValid($exception);
            }
            $this->cronboard->reportException($exception);
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

            if (! $aliasedTasks->isEmpty()) {
                return $aliasedTasks;
            }
        }
        return new Collection;
    }

    protected function loadTaskRuntime(Collection $tasksPayload, array $taskAliases)
    {
        $runtimeStorage = TaskRuntime::getStorage();
        $runtimeStorage->empty();

        $tasksPayloadByKey = $tasksPayload->keyBy('key');

        foreach ($tasksPayloadByKey as $taskKey => $taskData) {
            $runtime = TaskRuntime::fromTaskKey($taskKey);
            $this->applyDataToRuntime($runtime, $taskData);
        }

        foreach ($taskAliases as $aliasedTaskKey => $taskKey) {
            $runtime = TaskRuntime::inheritTaskRuntime($aliasedTaskKey, $taskKey);

            $taskData = $tasksPayloadByKey[$aliasedTaskKey] ?? ($tasksPayloadByKey[$taskKey] ?? []);
            $this->applyDataToRuntime($runtime, $taskData);
        }
    }

    protected function applyDataToRuntime(TaskRuntime $runtime, array $taskData)
    {
        $defaultContext = [
            'silent' => 0,
            'active' => 1,
            'once' => 0,
        ];

        $taskData = array_merge($defaultContext, $taskData);

        $runtime->setTracking(!$taskData['silent']);
        $runtime->setActive($taskData['active']);
        $runtime->setOnce($taskData['once']);
        $runtime->setOverrides($taskData['overrides'] ?? []);
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
        $task->setImmediateTask($taskData['once']);

        return $task;
    }
}
