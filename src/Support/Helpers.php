<?php

namespace Cronboard\Support;

use Cronboard\Tasks\Jobs\TrackedJob;
use Illuminate\Queue\Jobs\Job;
use Illuminate\Support\Arr;

class Helpers
{
    public static function implementsInterface(string $class, string $interface): bool
    {
        $implementedInterfaces = class_implements($class);
        return $implementedInterfaces && in_array($interface, array_values($implementedInterfaces));
    }

    public static function usesTrait(string $class, string $trait): bool
    {
        return in_array($trait, class_uses($class));
    }

    public static function getTrackedJobTaskKey(Job $job): ?string
    {
        $payload = $job->payload();
        if ($serializedJob = Arr::get($payload, 'data.command')) {
            $job = unserialize($serializedJob);
            if (isset($job->task)) {
                return $job->task;
            }
        }
        return null;
    }

    public static function isTrackedJob(Job $job): bool
    {
        return static::usesTrait(get_class($job), TrackedJob::class);
    }

    public static function isActiveTrackedJob(Job $job): bool
    {
        return static::isTrackedJob($job) && ! is_null(static::getTrackedJobTaskKey($job));
    }
}
