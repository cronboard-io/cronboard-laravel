<?php

namespace Cronboard\Core\Execution;

use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\Input\ArgumentParameter;
use Cronboard\Core\Reflection\Parameters\Input\OptionParameter;
use Illuminate\Console\Scheduling\Schedule;

class ExecuteCommandTask extends ExecuteTask
{
    public function attach(Schedule $schedule)
    {
        $command = $this->task->getCommand();

        $commandLineParameters = $this->getCommandLineParameters();

        return $schedule->command($command->getHandler(), $commandLineParameters);
    }

    public function getCommandLineParameters(): array
    {
        $taskParameters = $this->getTaskParameters();

        $consoleParameters = $taskParameters->getGroupParameters(Parameters::GROUP_CONSOLE)->toCollection();

        return $this->extractConsoleParameterValues($consoleParameters);
    }
}
