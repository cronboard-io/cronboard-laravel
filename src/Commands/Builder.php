<?php

namespace Cronboard\Commands;

use Cronboard\Commands\Build\FromClass;
use Cronboard\Commands\Build\FromObject;
use Cronboard\Commands\Build\FromScheduler;
use Illuminate\Contracts\Container\Container;

class Builder
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function fromClass(string $class): ?Command
    {
        $command = (new FromClass($this->container))->build($class);
        return $this->appendSupportData($command);
    }

    public function fromObject($object): ?Command
    {
        $command = (new FromObject($this->container))->build($object);
        return $this->appendSupportData($command);
    }

    public function fromScheduler(array $call): ?Command
    {
        $command = (new FromScheduler($this->container))->build($call);
        return $this->appendSupportData($command);
    }

    protected function appendSupportData(Command $command = null): ?Command
    {
        if (empty($command)) return $command;
        return (new CommandSupport)->extend($command);
    }
}
