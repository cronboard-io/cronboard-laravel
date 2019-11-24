<?php

namespace Cronboard\Tests\Stubs;

use Cronboard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class ConfigurableConsoleKernel extends ConsoleKernel
{
    protected $callbacks = [];

    protected function schedule(Schedule $schedule)
    {
        $schedule = Cronboard::extend($schedule);

        foreach ($this->callbacks as $callback) {
            $callback($schedule);
        }
    }

    public function modifySchedule($callback)
    {
        $this->callbacks[] = $callback;
    }
}
