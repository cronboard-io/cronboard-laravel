<?php

namespace Cronboard\Core;

use Cronboard\Facades\Cronboard as CronboardFacade;
use Cronboard\Runtime;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;

class Connector
{
    protected $cronboard;
    protected $cronboardRuntime;
    protected $connectable;

    public function __construct(Cronboard $cronboard, Runtime $runtime)
    {
        $this->cronboard = $cronboard;
        $this->cronboardRuntime = $runtime;
    }

    public function connect(LaravelSchedule $schedule): LaravelSchedule
    {
        return $this->getHandler()->connect($schedule);
    }

    public function reconnect(LaravelSchedule $schedule): LaravelSchedule
    {
        return $this->getHandler()->connect($schedule, true);
    }

    public function swapTemporary(Connectable $connectable)
    {
        $this->connectable = $connectable;
        $this->refresh();
    }

    public function restore()
    {
        $this->connectable = null;
        $this->refresh();
    }

    public function getHandler(): Connectable
    {
        return $this->connectable ?: $this->cronboard;
    }

    public function getRuntime()
    {
        return $this->connectable ?: $this->cronboardRuntime;
    }

    protected function refresh()
    {
        CronboardFacade::swap($this->getRuntime());
    }
}
