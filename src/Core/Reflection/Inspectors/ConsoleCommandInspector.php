<?php

namespace Cronboard\Core\Reflection\Inspectors;

use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\Input\ArgumentParameter;
use Cronboard\Core\Reflection\Parameters\Input\OptionParameter;
use Cronboard\Core\Reflection\Report;
use Illuminate\Console\Command;
use Illuminate\Container\Container;
use ReflectionClass;

class ConsoleCommandInspector extends Inspector
{
    public function getReport(): Report
    {
        $report = parent::getReport();

        $report->removeParameterGroup(Parameters::GROUP_CONSTRUCTOR);

        $instance = $this->getCommandInstance();
        if (is_null($instance)) {
            return new Report(false);
        }

        $inputDefinition = $instance->getDefinition();
        $parameters = [];

        foreach ($inputDefinition->getArguments() as $argument) {
            $parameters[] = ArgumentParameter::fromInputArgument($argument);
        }

        foreach ($inputDefinition->getOptions() as $option) {
            $parameters[] = OptionParameter::fromInputOption($option);
        }

        $report->addParameterGroup(Parameters::GROUP_CONSOLE, $parameters);

        return $report;
    }

    protected function getCommandInstance(): ?Command
    {
        if (!class_exists($this->class)) {
            return null;
        }

        $reflectionClass = new ReflectionClass($this->class);
        if (!$reflectionClass->isInstantiable()) {
            return null;
        }

        $container = Container::getInstance();

        return $container->make($this->class);
    }
}
