<?php

namespace Cronboard\Commands\Build;

use Cronboard\Commands\Command;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;

class FromClass
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function build($class): ?Command
    {
        return $this->getCommandFromClass($class);
    }

    public function buildWithInstance(string $class, $instance = null): ?Command
    {
        return $this->getCommandFromClass($class, $instance);
    }

    protected function getCommandFromClass(string $class, $instance = null): ?Command
    {
        if (! class_exists($class)) {
            return null;
        }
        if ($this->isConsoleCommand($class)) {
            $alias = null;
            if (! empty($class) && class_exists($class)) {
                $instance = $instance ?: $this->container->make($class);
                $alias = $instance->getName();
            }
            return Command::command($class, $alias);
        } else if ($this->isQueueJob($class)) {
            return Command::job($class);
        } else if ($this->isInvokable($class)) {
            return Command::invokable($class);
        } else if ($this->isClosure($class)) {
            return Command::closure();
        }
        return null;
    }

    private function isClosure(string $class)
    {
        return $class === 'Closure';
    }

    private function isInvokable(string $class)
    {
        return method_exists($class, '__invoke');
    }

    private function isConsoleCommand(string $class)
    {
        return is_subclass_of($class, ConsoleCommand::class);
    }

    private function isQueueJob(string $class)
    {
        $interfaces = class_implements($class);
        return in_array(ShouldQueue::class, class_implements($class));
    }
}

