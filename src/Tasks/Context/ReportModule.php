<?php

namespace Cronboard\Tasks\Context;

class ReportModule extends Module
{
	protected $report = [];

	public function load(array $data)
	{
		$this->report = $data['report'] ?? [];
	}

	public function toArray(): array
	{
		return [
			'report' => $this->report
		];
	}

	public function getHooks(): array
	{
		return [
			'report',
			'getReport'
		];
	}

	public function shouldStoreAfter(string $hookName): bool
	{
		return $hookName === 'report';
	}

	public function report($key, $value)
    {
        $this->report[$key] = $value;
    }

    public function getReport(): array
    {
        return $this->report;
    }
}
