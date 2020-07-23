<?php

namespace Cronboard\Core\Api\Endpoints;

use Carbon\Carbon;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskRuntime;

class Tasks extends Endpoint
{
    public function start(Task $task, TaskRuntime $context)
    {
        $payload = $this->getTaskStartedPayload($task, $context);

        return $this->post('tasks/start', $payload);
    }

    public function end(Task $task, TaskRuntime $context)
    {
        $payload = $this->getTaskFinishedPayload($task, $context);

        return $this->post('tasks/end', $payload);
    }

    public function fail(Task $task, TaskRuntime $context)
    {
        $payload = $this->getTaskFinishedPayload($task, $context);

        return $this->post('tasks/fail', $payload);
    }

    public function queue(Task $task, TaskRuntime $context)
    {
        $payload = $this->getTaskStartedPayload($task, $context);

        return $this->post('tasks/queue', $payload);
    }

    public function output(Task $task, string $output)
    {
        $payload = [
            'key' => $task->getKey(),
            'output' => $output
        ];

        return $this->post('tasks/output', $payload);
    }

    protected function getTaskStartedPayload(Task $task, TaskRuntime $context): array
    {
        $payload = [
            'key' => $task->getKey(),
            'context' => $context->getExecutionContext()->toArray(),
            'environment' => $context->getEnvironment()->toArray(),
            'timestamp' => $this->getCurrentTimestamp()
        ];
        return $payload;
    }

    protected function getTaskFinishedPayload(Task $task, TaskRuntime $context): array
    {
        $payload = [
            'key' => $task->getKey(),
            'report' => $context->getReport(),
            'metrics' => $context->getCollector()->toArray(),
            'exception' => $context->getException(),
            'timestamp' => $this->getCurrentTimestamp()
        ];
        return $payload;
    }

    protected function getCurrentTimestamp(): int
    {
        return Carbon::now()->timestamp;
    }
}
