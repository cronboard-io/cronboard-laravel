<?php

namespace Cronboard\Core\Execution;

use Cronboard\Core\Reflection\Parameters;
use Illuminate\Console\Scheduling\Schedule;

class ExecuteJobTask extends ExecuteTask
{
	public function attach(Schedule $schedule)
	{
		$command = $this->task->getCommand();
		$commandParameters = $this->getTaskParameters();

		$scheduleParameters = $commandParameters->getGroupParameters(Parameters::GROUP_SCHEDULE)->toCollection();
		$scheduleParameters = $scheduleParameters->keyBy(function($parameter){
			return $parameter->getName();
		})->map->getValue();

		$job = $this->getJobInstance();

		return $schedule->job($job, $scheduleParameters->get('queue'), $scheduleParameters->get('connection'));
	}

	public function getJobInstance()
	{
		$command = $this->task->getCommand();
		$commandParameters = $this->getTaskParameters();

		$constructorParameters = $commandParameters->getGroupParameters(Parameters::GROUP_CONSTRUCTOR)->toCollection();
		$constructorParameters = $this->extractParameterValues($constructorParameters);

		$jobClass = $command->getHandler();

		return $this->app->make($command->getHandler(), $constructorParameters);
	}
}
