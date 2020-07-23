<?php

namespace Cronboard\Tasks;

use Cronboard\Support\Testing;
use Illuminate\Support\Env;

class Resolver
{
    const ENV_VAR = 'CRONBOARD_TASK';

    public static function resolveFromEnvironment(): ?string
    {
        $key = static::getTaskKeyEnvHelper();

        if (! $key) {
            $key = static::getTaskKeyFromEnvArray();
        }

        if (! $key) {
            $key = static::getTaskKeyFromServerArray();
        }

        if (! $key) {
            $key = static::getTaskFromTestingEnvironment();
        }

        return $key;
    }

    private static function getTaskKeyEnvHelper(): ?string
    {
        if (class_exists(Env::class)) {
            return Env::get(static::ENV_VAR);
        } else if (function_exists('env')) {
            return env(static::ENV_VAR);
        }
    }

    private static function getTaskKeyFromEnvArray(): ?string
    {
        return $_ENV[static::ENV_VAR] ?? null;
    }

    private static function getTaskKeyFromServerArray(): ?string
    {
        return $_SERVER[static::ENV_VAR] ?? null;
    }

    private static function getTaskFromTestingEnvironment(): ?string
    {
        return Testing::getCurrentTask();
    }
}
