<?php

namespace Cronboard\Core\Execution\Events;

use Cronboard\Tasks\Task;

class TaskFailed
{
    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}