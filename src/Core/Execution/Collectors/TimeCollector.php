<?php

namespace Cronboard\Core\Execution\Collectors;

class TimeCollector extends Collector
{
    protected $startTime;
    protected $endTime;

    public function start()
    {
        $this->startTime = microtime(true);
    }

    public function end()
    {
        $this->endTime = microtime(true);
    }

    public function toArray(): array
    {
        return [
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'duration' => $this->endTime - $this->startTime,
            'format' => 'seconds'
        ];
    }
}
