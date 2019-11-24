<?php

namespace Cronboard\Tests\Stubs;

class ContextRecordInvokable
{
    protected $envToRecord;
    protected $configToRecord;

    protected static $env = [];
    protected static $config = [];

    public function __construct(array $envToRecord = [], array $configToRecord = [])
    {
        $this->envToRecord = $envToRecord;
        $this->configToRecord = $configToRecord;

        static::$env = [];
        static::$config = [];
    }

    public function __invoke()
    {
        foreach ($this->envToRecord as $key) {
            static::$env[$key] = env($key);
        }

        foreach ($this->configToRecord as $key) {
            static::$config[$key] = config($key);
        }
    }

    public static function getEnvironmentVariables(): array
    {
        return static::$env;
    }

    public static function getConfigurationSettings(): array
    {
        return static::$config;
    }
}
