<?php

namespace Cronboard\Core\Execution\Listeners;

use Cronboard\Tasks\Task;

class ExecEventSubscriber extends CallableEventSubscriber
{
    protected function isTaskSupported(Task $task): bool
    {
        return $task->getCommand()->isExecCommand();
    }
}