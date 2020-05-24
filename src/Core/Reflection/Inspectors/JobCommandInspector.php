<?php

namespace Cronboard\Core\Reflection\Inspectors;

use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\Primitive\StringParameter;
use Cronboard\Core\Reflection\Report;

class JobCommandInspector extends Inspector
{
    public function getReport(): Report
    {
        $report = parent::getReport();

        $report->addParameterGroup(Parameters::GROUP_SCHEDULE, [
            StringParameter::create('queue')
                ->setDefault(null)
                ->setRequired(false),

            StringParameter::create('connection')
                ->setDefault(null)
                ->setRequired(false)
        ]);

        return $report;
    }
}
