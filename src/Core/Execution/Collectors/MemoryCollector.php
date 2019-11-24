<?php

namespace Cronboard\Core\Execution\Collectors;

class MemoryCollector extends Collector
{
    protected $memoryUsageInBytes;
    protected $memoryLimitInBytes;
    protected $noLimit = false;
    protected $realUsage = false;

    public function start()
    {
        $memoryLimitValue = ini_get('memory_limit');
        $this->noLimit = $memoryLimitValue == '-1';
        $this->memoryLimitInBytes = $this->memoryLimitToBytes($memoryLimitValue);
    }

    public function end()
    {
        $this->memoryUsageInBytes = memory_get_peak_usage($this->realUsage);
    }

    private function memoryLimitToBytes($memoryLimit)
    {
        $memoryLimit = trim($memoryLimit);
        $last = strtolower($memoryLimit[strlen($memoryLimit)-1]);
        $memoryLimit = substr($memoryLimit, 0, -1);

        switch($last) {
            case 'g':
                $memoryLimit *= 1024;
            case 'm':
                $memoryLimit *= 1024;
            case 'k':
                $memoryLimit *= 1024;
        }

        return $memoryLimit;
    }

    public function toArray(): array
    {
        return [
            'noLimit' => $this->noLimit,
            'peakUsage' => $this->memoryUsageInBytes,
            'realUsage' => $this->realUsage,
            'memoryLimit' => $this->memoryLimitInBytes,
            'format' => 'bytes'
        ];
    }
}
