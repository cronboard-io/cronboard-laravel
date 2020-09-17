<?php

namespace Cronboard\Core\Execution\Events;

use Cronboard\Tasks\Task;
use Throwable;

class TaskFailed
{
    public $task;
    public $exception;

    public function __construct(Task $task, Throwable $exception)
    {
        $this->task = $task;
        $this->exception = $exception;
    }
}