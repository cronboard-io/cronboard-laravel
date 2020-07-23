<?php

namespace Cronboard\Tests\Stubs\Api;

use Illuminate\Contracts\Support\Arrayable;

class TaskPayload implements Arrayable
{
	public function toArray()
	{
		return [
			"source" => "app",
            "name" => "CronboardTestJob",
            "command" => [
            	"type" => "job",
                "key" => "369d524fb1d13b91f9449e3e9474dd2c",
                "handler" => "Cronboard\\Tests\\Stubs\\CronboardTestJob"
            ],
            "key" => "acf786445fc9676bb7b92e71bf051d15b0a17075",
            "parameters" => [
            	"constructor" => [],
                "schedule" => [
                    [
                    	"name" => "queue",
                        "value" => null,
                        "type" => "string",
                        "required" => false,
                        "default" => null,
                        "id" => "deb4da54d0c539b3c57f9776c74d36c6"
                    ],
                    [
                    	"name" => "connection",
                        "value" => null,
                        "type" => "string",
                        "required" => false,
                        "default" => null,
                        "id" => "73e456be735fff276f1c6fcab4e30f98"
                    ]
                ]
            ],
            "constraints" => [
                [
                    "everyFiveMinutes",
                    []
                ]
            ],
            "overrides" => [],
            "active" => 1,
            "silent" => 0,
            "once" => 0,
		];
	}

    public static function create(): array
    {
        return (new static)->toArray();
    }

    public static function invokable(): array
    {
        return static::createWithType('invokable', "Cronboard\\Tests\\Stubs\\CronboardTestInvokable");
    }

    public static function command(): array
    {
        return static::createWithType('command', "Cronboard\\Tests\\Stubs\\CronboardTestCommand");
    }

    public static function exec(): array
    {
        return static::createWithType('exec', 'ls -la');
    }

    public static function job(array $constructor = null): array
    {
        $payload = static::createWithType('job');
        if (! is_null($constructor)) {
            $payload['parameters']['constructor'] = $constructor;
        }
        return $payload;
    }

    protected static function createWithType(string $type, string $handler = null): array
    {
        $payload = static::create();
        $payload['command']['handler'] = $handler ?: $payload['command']['handler'];
        $payload['command']['type'] = $type;
        $payload['command']['key'] = md5(implode('-', [$type, $payload['command']['handler']]));
        return $payload;
    }

    public static function immediate(): array
    {
        $payload = static::create();
        $payload['key'] = md5(uniqid());
        $payload['once'] = 1;
        return $payload;
    }

    public static function queued(): array
    {
        $payload = static::create();
        $payload['key'] = md5(uniqid());
        return $payload;
    }
}