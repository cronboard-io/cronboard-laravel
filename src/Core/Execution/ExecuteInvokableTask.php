<?php

namespace Cronboard\Core\Execution;

use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\Parameter;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

class ExecuteInvokableTask extends ExecuteTask
{
	public function attach(Schedule $schedule)
	{
		return $schedule->call($this->getInvokableInstance(), $this->getInvokeParameters());
	}

	public function getInvokableInstance()
	{
		$command = $this->task->getCommand();

		$taskParameters = $this->getTaskParameters();
		$constructorParameters = $taskParameters->getGroupParameters(Parameters::GROUP_CONSTRUCTOR)->toCollection();
		$constructorParameters = $this->extractParameterValues($constructorParameters);

		return $this->app->make($command->getHandler(), $constructorParameters);
	}

	public function getInvokeParameters(): array
	{
		$command = $this->task->getCommand();

		$taskParameters = $this->getTaskParameters();
		$invokeParameters = $taskParameters->getGroupParameters(Parameters::GROUP_INVOKE)->toCollection();
		$invokeParameters = $this->extractParameterValues($invokeParameters);

		return $invokeParameters;
	}
}
