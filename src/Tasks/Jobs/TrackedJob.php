<?php

namespace Cronboard\Tasks\Jobs;

trait TrackedJob
{
	protected $taskKey;

	public function getTaskKey(): ?string
	{
		return $this->taskKey;
	}

	public function setTaskKey(string $key)
	{
		$this->taskKey = $key;
	}
}
