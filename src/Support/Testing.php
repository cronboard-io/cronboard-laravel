<?php

namespace Cronboard\Support;

class Testing
{
    protected static $currentTask;

    public static function setCurrentTask(string $key = null)
    {
        static::$currentTask = $key;
    }

    public static function getCurrentTask()
    {
        return static::$currentTask;
    }
}
