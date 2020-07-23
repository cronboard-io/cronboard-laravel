<?php

namespace Cronboard;

use BadMethodCallException;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Tasks\TaskRuntime;
use Illuminate\Container\Container;

class Runtime
{
    public function __call($method, $arguments)
    {
        if (in_array($method, $this->getContextMethods())) {
            if ($runtime = $this->getTaskRuntime()) {
                return call_user_func_array([$runtime, $method], $arguments);
            }
            return;
        }

        throw new BadMethodCallException("Method [$method] does not exist");
    }

    private function getTaskRuntime(): ?TaskRuntime
    {
        if ($task = TaskContext::getTask()) {
            return TaskRuntime::fromTask($task);
        }
        return null;
    }

    private function getContextMethods(): array
    {
        return [
            'report'
        ];
    }
}
