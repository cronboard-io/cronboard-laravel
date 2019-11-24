<?php

namespace Cronboard\Core\Reflection\Inspectors;

use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Report;
use ReflectionClass;

class InvokableCommandInspector extends Inspector
{
    public function getReport(): Report
    {
        $report = parent::getReport();

        $invokeMethod = (new ReflectionClass($this->class))->getMethod('__invoke');

        $parameters = $this->inspectMethodParameters($invokeMethod);

        $report->addParameterGroup(Parameters::GROUP_INVOKE, $parameters);

        return $report;
    }
}
