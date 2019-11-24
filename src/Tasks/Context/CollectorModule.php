<?php

namespace Cronboard\Tasks\Context;

use Cronboard\Core\Execution\Collectors\Collector;
use Cronboard\Core\Execution\Collectors\MemoryCollector;
use Cronboard\Core\Execution\Collectors\TimeCollector;

class CollectorModule extends Module
{
	protected $collector;

	public function load(array $data)
	{
		$collector = $data['collector'] ?? null;
		$this->collector = $collector ? unserialize($collector) : new Collector;
	}

	public function toArray(): array
	{
		return [
			'collector' => serialize($this->collector)
		];
	}

	public function getHooks(): array
	{
		return [
			'getCollector',
		];
	}

	public function onContextEnter()
	{
		$this->collector = new Collector([
            new MemoryCollector,
            new TimeCollector
        ]);
        $this->collector->start();
	}

	public function onContextFinalise()
	{
		$this->collector->end();
	}

	public function getCollector(): Collector
    {
        return $this->collector;
    }
}
