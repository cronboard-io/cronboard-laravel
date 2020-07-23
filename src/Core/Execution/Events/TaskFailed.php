<?php

namespace Cronboard\Core\Execution\Events;

use Cronboard\Tasks\Task;
use Exception;

class TaskFailed
{
    public $task;
    public $exception;

    public function __construct(Task $task, Exception $exception)
    {
        $this->task = $task;
        $this->exception = $exception;
    }
}