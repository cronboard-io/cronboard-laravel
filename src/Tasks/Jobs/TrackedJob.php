<?php

namespace Cronboard\Tasks\Jobs;

use Cronboard\Tasks\Task;

trait TrackedJob
{
    public $task;

    public function getTask(): ?string
    {
        return $this->task;
    }

    public function setTask(Task $task)
    {
        $this->task = $task->getKey();
    }
}
