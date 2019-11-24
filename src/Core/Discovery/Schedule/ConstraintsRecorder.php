<?php

namespace Cronboard\Core\Discovery\Schedule;

use Illuminate\Console\Scheduling\Schedule;

class ConstraintsRecorder extends Schedule
{
    protected $constraints;

    public function __construct()
    {
        $this->constraints = [];
    }

    public function getConstraintsData(): array
    {
        return $this->constraints;
    }

    public function __call($method, $args)
    {
        $this->record($method, $args);
        return $this;
    }

    protected function record($method, $args)
    {
        $this->constraints[] = compact('method', 'args');
    }

    public function call($callback, array $parameters = [])
    {
        return $this;
    }

    public function command($command, array $parameters = [])
    {
        return $this;
    }

    public function job($job, $queue = null, $connection = null)
    {
        return $this;
    }

    public function exec($command, array $parameters = [])
    {
        return $this;
    }
}
