<?php

namespace Cronboard\Commands;

use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Container\Container;
use Illuminate\Contracts\Container\BindingResolutionException;
use ReflectionClass;

class MetadataExtractor
{
    protected function getMetadataFromClass(string $class)
    {
        if (class_exists($class)) {
            $reflectionClass = new ReflectionClass($class);
            if ($reflectionClass->isInstantiable()) {
                $instance = $this->resolve($class);
                if ($instance) {
                    return $this->getMetadataFromObject($instance);
                }
            }
        }
        return null;
    }

    protected function resolve(string $class)
    {
        $container = Container::getInstance();
        try {
            return $container->make($class);
        } catch (BindingResolutionException $e) {}

        return null;
    }

    public function getMetadataFromObject($object)
    {
        $name = null;
        $description = null;

        if ($object instanceof Command) {
            $result = $this->getMetadataFromClass($object->getHandler());
            if (! empty($result)) {
                extract($result);
            }
        } else if ($object instanceof ConsoleCommand) {
            $name = $object->getName();
            $description = $object->getDescription();
        } else if ($object instanceof CommandMetadataProvider) {
            $name = $object->getCommandName();
            $description = $object->getCommandDescription();
        } else {

        }

        return compact('name', 'description');
    }
}
