<?php

namespace Cronboard\Tasks\Events;

use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\TaskKey;
use Illuminate\Console\Scheduling\Event as SchedulingEvent;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\Process\Process;

class Event extends SchedulingEvent
{
    use TrackedEvent;

    protected function runCommandInForeground(Container $container)
    {
        $this->callBeforeCallbacks($container);

        $this->exitCode = $this->runAsProcess();

        $this->callAfterCallbacks($container);
    }

    /**
     * Run the command in the background.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return void
     */
    protected function runCommandInBackground(Container $container)
    {
        $this->callBeforeCallbacks($container);

        $this->runAsProcess();
    }

    protected function runAsProcess()
    {
        $process = new Process(
            $this->buildCommand(), base_path(), $this->getProcessEnvironment(), null, null
        );
        return $process->run();
    }

    protected function getProcessEnvironment(): array
    {
        return [
            Resolver::TASK_KEY_ENV_VAR => $this->task ? $this->task->getKey() : TaskKey::createFromEvent($this)
        ];
    }
}
