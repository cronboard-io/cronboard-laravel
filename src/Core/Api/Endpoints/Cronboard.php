<?php

namespace Cronboard\Core\Api\Endpoints;

use Cronboard\Commands\MetadataExtractor as CommandMetadataExtractor;
use Cronboard\Tasks\MetadataExtractor as TaskMetadataExtractor;
use Illuminate\Support\Collection;

class Cronboard extends Endpoint
{
    public function validateToken(string $token)
    {
        return $this->postWithoutVerification('cronboard/token-validate', compact('token'));
    }

    public function install(string $token, array $environment)
    {
        $data = array_merge(compact('token'), compact('environment'));
        return $this->postWithoutVerification('cronboard/install', $data);
    }

    public function record(Collection $commands, Collection $tasks, array $environment = [])
    {
        $payload = [
            'commands' => $this->formatCommands($commands),
            'tasks' => $this->formatTasks($tasks),
            'environment' => $environment,
        ];

        return $this->post('cronboard/record', $payload);
    }

    public function schedule(string $env = null)
    {
        $query = http_build_query(array_filter(compact('env')));
        return $this->get('cronboard' . ($query ? '?' . $query : ''));
    }

    protected function formatCollection(Collection $collection)
    {
        return $collection->map->toArray()->all();
    }

    protected function formatTasks(Collection $tasks)
    {
        $metadataExtractor = new TaskMetadataExtractor;
        return $tasks->map(function($task) use ($metadataExtractor) {
            return array_merge($task->toArray(), $metadataExtractor->getMetadataFromObject($task));
        })->all();
    }

    protected function formatCommands(Collection $commands)
    {
        $metadataExtractor = new CommandMetadataExtractor;
        return $commands->map(function($command) use ($metadataExtractor) {
            return array_merge($command->toArray(), $metadataExtractor->getMetadataFromObject($command));
        })->all();
    }
}
