<?php

namespace Cronboard\Support;

use Illuminate\Contracts\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

class CommandContext
{
    protected $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function isConsoleCommandContext(string $class): bool
    {
        if (!$this->app->runningInConsole()) {
            return false;
        }

        $commandName = $this->app->make($class)->getName();
        $consoleCommandNameArgument = (new ArgvInput)->getFirstArgument();

        return $commandName === $consoleCommandNameArgument;
    }
}
