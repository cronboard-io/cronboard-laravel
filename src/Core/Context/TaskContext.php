<?php

namespace Cronboard\Core\Context;

use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskRuntime;

class TaskContext
{
    protected static $task;

    public static function enter(Task $task): TaskRuntime
    {
    	static::$task = $task;

        $runtime = TaskRuntime::fromTask($task);
        $runtime->enter();

        return $runtime;
    }

    public static function exit()
    {
        if (static::$task) {
            $runtime = TaskRuntime::fromTask(static::$task);
            $runtime->exitAndKeep();
        }
    	static::$task = null;
    }

    public static function getTask(): ?Task
    {
    	return static::$task;
    }
}
