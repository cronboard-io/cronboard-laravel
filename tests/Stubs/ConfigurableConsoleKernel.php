<?php

namespace Cronboard\Tests\Stubs;

use Cronboard;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class ConfigurableConsoleKernel extends ConsoleKernel
{
    protected static $callbacks = [];

    protected function schedule(Schedule $schedule)
    {
        $schedule = Cronboard::extend($schedule, $reset = true);

        foreach (static::$callbacks as $callback) {
            $callback($schedule);
        }
    }

    public static function modifySchedule($callback)
    {
        static::$callbacks = [$callback];
    }
}
