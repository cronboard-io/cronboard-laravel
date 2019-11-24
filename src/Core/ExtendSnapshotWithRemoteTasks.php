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

            $response = $this->app->make(Cronboard::class)->cronboard($environment);

            $tasksByKey = Collection::wrap($response['tasks'])->keyBy('key');
            $queuedTasksByKey = Collection::wrap($response['queuedTasks'])->keyBy('key');

            $tasks = $tasksByKey->merge($queuedTasksByKey);

            // load remote tasks into snapshot (and into cronboard)
            $taskObjects = $this->createTasksFromPayload($tasks, $snapshot);

            $taskAliases = $response['aliases'] ?? [];
            if (! empty($taskAliases)) {
                $aliasedTaskObjects = $this->createTasksFromTaskAliases($snapshot->getTasks(), $taskAliases, $tasks);
                $taskObjects = $taskObjects->merge($aliasedTaskObjects);

                // add context data for aliased tasks
                foreach ($taskAliases as $taskKey => $aliasedTaskKey) {
                    $tasks[$aliasedTaskKey] = $tasks[$aliasedTaskKey] ?? ($tasks[$taskKey] ?? []);
                }
            }

            $snapshot->addRemoteTasks($taskObjects);

            if ($this->shouldStoreSnapshot($snapshot)) {
                $this->storeSnapshot($snapshot);
            }

            // load contexts for all tasks and store locally
            $this->loadTaskContext($tasks);

        } catch (Exception $e) {
            // report exception and return snapshot untouched
            $this->cronboard->reportException($e);
        }

        return $snapshot;
    }

    protected function createTasksFromTaskAliases(Collection $snapshotTasks, array $aliases, Collection $tasksPayload)
    {
        $keys = array_keys($aliases);
        if (! empty($keys)) {
            $aliasedTasks = $snapshotTasks->map(function($task) use ($keys, $aliases, $tasksPayload) {
                $taskKey = $task->getKey();

                if (in_array($taskKey, $keys)) {
                    $aliasedKey = $aliases[$taskKey];
                    $taskData = $tasksPayload[$aliasedKey] ?? ($tasksPayload[$taskKey] ?? null);

                    $aliasedTask = $task->aliasAsCustomTask($aliasedKey);
                    $aliasedTask->setSingleExecution($taskData['once'] ?? false);

                    return $aliasedTask;
                }
                return null;
            })->filter();

            if (! $aliasedTasks->isEmpty()) {
                return $aliasedTasks->keyBy->getKey();
            }
        }
        return new Collection;
    }

    protected function shouldStoreSnapshot(Snapshot $snapshot)
    {
        return ! empty($customScheduledTask = $snapshot->getTasks()->first(function($task){
            return $task->isCustomTask() && ! $task->isSingleExecution();
        }));
    }

    protected function loadTaskContext(Collection $tasksPayload)
    {
        $defaultContext = [
            'silent' => 0,
            'active' => 1,
            'once' => 0,
        ];
        foreach ($tasksPayload as $taskKey => $taskData) {
            $taskData = array_merge($defaultContext, $taskData);

            $context = new TaskContext($this->app, $taskKey);
            $context->setTracking(! $taskData['silent']);
            $context->setActive($taskData['active']);
            $context->setOnce($taskData['once']);
            $context->setOverrides($taskData['overrides'] ?? []);
        }
    }

    protected function createTasksFromPayload(Collection $tasksPayload, Snapshot $snapshot): Collection
    {
        $tasks = Collection::wrap($tasksPayload)
            // filter to only custom tasks
            ->filter(function($taskData){
                return $this->isDefinedByCronboard($taskData);
            })
            // create task objects
            ->map(function($taskData, $taskKey) use ($snapshot) {
                return $this->createTaskFromPayload($taskData, $snapshot);
            })
            // clear invalid tasks
            ->filter();

        return $tasks->keyBy->getKey();
    }

    protected function isDefinedByCronboard(array $taskData): bool
    {
        return $taskData['source'] === 'cronboard';
    }

    protected function createTaskFromPayload(array $taskData, Snapshot $snapshot): ?Task
    {
        $command = $snapshot->getCommandByKey($taskData['command']['key']);
        if (empty($command)) return null;

        $parameters = (new ParseParameters)->execute($taskData['parameters']);
        $task = new Task($taskData['key'], $command, $parameters, $taskData['constraints']);
        $task->setDetails(Arr::only($taskData, 'name'));

        $task->setCustomTask(true);
        $task->setSingleExecution($taskData['once']);

        return $task;
    }
}
