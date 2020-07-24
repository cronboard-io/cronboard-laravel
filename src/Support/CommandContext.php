<?php

namespace Cronboard\Support;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Symfony\Component\Console\Input\ArgvInput;

class CommandContext
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function inCommandContext(string $command, bool $runningInConsole = null): bool
    {
        if (is_null($runningInConsole) && ! $this->app->runningInConsole()) {
            return false;
        }

        $commandName = $command;
        if (class_exists($command)) {
            $commandName = $this->app->make($command)->getName();
        }

        $consoleCommandName = $this->getConsoleCommandName();

        return $commandName === $consoleCommandName;
    }

    public function inCommandsContext(Collection $commands): bool
    {
        $runningInConsole = $this->app->runningInConsole();

        if (! $runningInConsole) {
            return false;
        }

        foreach ($commands as $command) {
            if ($this->inCommandContext($command, $runningInConsole)) {
                return true;
            }
        }

        return false;
    }

    private function getConsoleCommandName(): ?string
    {
        static $commandName = null;

        if (is_null($commandName)) {
            $commandName = (new ArgvInput)->getFirstArgument();
        }

        return $commandName;
    }

    public function getSchedulerContextCommands(): array
    {
        return ['schedule:run', 'schedule:finish'];
    }

    public function getQueueWorkerContextCommands(): array
    {
        return ['queue:work', 'queue:listen'];
    }

    public function isSchedulerContext(): bool
    {
        return $this->inCommandsContext(new Collection($this->getSchedulerContextCommands()));
    }

    public function isQueueWorkerContext(): bool
    {
        return $this->inCommandsContext(new Collection($this->getQueueWorkerContextCommands()));
    }
}
