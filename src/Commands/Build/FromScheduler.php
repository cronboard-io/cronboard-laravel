<?php

namespace Cronboard\Commands\Build;

use Closure;
use Cronboard\Commands\Command;
use Illuminate\Console\Parser;
use Illuminate\Contracts\Container\Container;

class FromScheduler
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function build(array $call): ?Command
    {
        $method = $call['method'];

        if ($method === 'call') {
            return $this->createInvokableCommand($call);
        }

        if ($method === 'exec') {
            return $this->createExecCommand($call);
        }

        if ($method === 'command') {
            return $this->createConsoleCommand($call);
        }

        if ($method === 'job') {
            return $this->createJobCommand($call);
        }

        return null;
    }

    protected function createJobCommand(array $call): ?Command
    {
        $argument = $call['args'][0] ?? null;
        if (empty($argument)) {
            return null;
        }
        $class = is_object($argument) ? get_class($argument) : $argument;
        return Command::job($class);
    }

    protected function createConsoleCommand(array $call): Command
    {
        $command = $call['args'][0];
        [$commandName, $arguments, $options] = Parser::parse($command);
        return class_exists($commandName) ? Command::command($commandName, $this->container->make($commandName)->getName()) : Command::command()->byAlias($commandName);
    }

    protected function createExecCommand(array $call): Command
    {
        return Command::exec($call['args'][0]);
    }

    protected function createInvokableCommand(array $call): ?Command
    {
        $argument = $call['args'][0] ?? null;
        if (empty($argument)) {
            return null;
        }

        if ($argument instanceof Closure) {
            return Command::closure();
        }

        if (is_string($argument)) {
            return Command::invokable($argument);
        }

        if ($this->isInvokable($argument)) {
            return Command::invokable(get_class($argument));
        }

        return Command::closure();
    }

    private function isInvokable($object)
    {
        return method_exists($object, '__invoke');
    }
}

