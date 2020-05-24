<?php

namespace Cronboard\Commands;

use Illuminate\Container\Container;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use ReflectionClass;

class Registry
{
    protected $commands;
    protected $namespaces;

    public function __construct(Container $container, Collection $commands = null)
    {
        $this->commands = $commands ?: new Collection;

        $this->namespaces = [
            'App\\', $container->make(Application::class)->getNamespace()
        ];

        $this->refreshIndex();
    }

    public function register(Command $command)
    {
        $this->commands[] = $command;
        $this->refreshIndex();
    }

    protected function refreshIndex()
    {
        $this->commands = $this->commands->filter(function($command) {
            return !$command->isConsoleCommand() || $this->isAcceptedConsoleCommand($command);
        })->keyBy->getKey()->values();
    }

    public function getCommands()
    {
        return $this->commands;
    }

    protected function isAcceptedConsoleCommand(Command $command): bool
    {
        return true;
        // return $this->isCustomConsoleCommand($command);
    }

    protected function isCustomConsoleCommand(Command $command): bool
    {
        return Str::startsWith($this->getNamespace($command), $this->namespaces);
    }

    protected function getNamespace(Command $command): string
    {
        return (new ReflectionClass($command->getHandler()))->getNamespaceName();
    }
}
