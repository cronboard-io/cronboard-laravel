<?php

namespace Cronboard\Core\Execution;

use Cronboard\Core\Reflection\Parameters;
use Illuminate\Console\Scheduling\Schedule;

class ExecuteExecTask extends ExecuteCommandTask
{
    public function attach(Schedule $schedule)
    {
        $command = $this->task->getCommand();
		
        $commandLineParameters = $this->getCommandLineParameters();

        return $schedule->exec($command->getHandler(), $commandLineParameters);
    }
}
