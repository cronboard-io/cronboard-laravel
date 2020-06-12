<?php

namespace Cronboard\Commands;

use Cronboard\Commands\Build\FromClass;
use Cronboard\Commands\Build\FromObject;
use Cronboard\Commands\Build\FromScheduler;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;

class CommandBuilderException extends \Exception {};

class Builder
{
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function fromClass(string $class): ?Command
    {
        $command = $this->build(new FromClass($this->container), $class);
        return $this->appendSupportData($command);
    }

    public function fromObject($object): ?Command
    {
        $command = $this->build(new FromObject($this->container), $object);
        return $this->appendSupportData($command);
    }

    public function fromScheduler(array $call): ?Command
    {
        $command = $this->build(new FromScheduler($this->container), $call);
        return $this->appendSupportData($command);
    }

    protected function build($factory, $target)
    {
        try {
            return $factory->build($target);
        } catch (BindingResolutionException $e) {
            throw new CommandBuilderException('Could not record command: ' . $e->getMessage(), $e->getCode(), $e);
        }
    }

    protected function appendSupportData(Command $command = null): ?Command
    {
        if (empty($command)) {
            return $command;
        }
        return (new CommandSupport)->extend($command);
    }
}
