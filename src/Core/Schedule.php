<?php

namespace Cronboard\Core;

use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Support\Traits\Macroable;

class Schedule extends LaravelSchedule
{
    use Macroable;

    public static function createWithEventsFrom(LaravelSchedule $schedule): Schedule
    {
        $instance = new static;
        $instance->events = $schedule->events();
        return $instance;
    }
}
