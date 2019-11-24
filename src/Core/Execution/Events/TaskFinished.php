<?php

namespace Cronboard\Core\Execution\Events;

use Cronboard\Tasks\Task;

class TaskFinished
{
    public $task;

    public function __construct(Task $task)
    {
        $this->task = $task;
    }
}