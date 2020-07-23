<?php

namespace Cronboard\Tests\Stubs\Api;

use Cronboard\Tests\Support\TestCommand;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class SchedulePayload implements Arrayable
{
	protected $tasks;
	protected $queuedTasks;
	protected $aliases;

	public function __construct()
	{
		$this->tasks = new Collection;
		$this->queuedTasks = new Collection;
		$this->aliases = [];
	}

	public function toArray()
	{
		return [
			'tasks' => $this->getTasks()->toArray(),
			'queuedTasks' => $this->getQueuedTasks()->toArray(),
			'aliases' => $this->getAliases(),
		];
	}

	protected function getTasks(): Collection
	{
		return $this->tasks;
	}

	protected function getQueuedTasks(): Collection
	{
		return $this->queuedTasks;
	}

	protected function getAliases(): array
	{
		return $this->aliases;
	}

	public function addTask(array $task)
	{
		$this->tasks[] = $task;
	}

	public function addQueuedTask(array $queuedTask, array $task)
	{
		$this->addTask($task);
		$this->queuedTasks[] = $queuedTask;
		$this->aliases[$queuedTask['key']] = $task['key'];
	}

	public function getCommands(): Collection
	{
		return (new Collection($this->tasks))->map(function($taskPayload){
			return new TestCommand(
				$taskPayload['command']['type'],
				$taskPayload['command']['handler'],
				$taskPayload['command']['alias'] ?? null
			);
		});
	}

}