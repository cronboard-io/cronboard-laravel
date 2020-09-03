<?php

namespace Cronboard\Core;

use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Support\Traits\Macroable;

class Schedule extends LaravelSchedule
{
    use Macroable;

    public static function createWithEventsFrom(LaravelSchedule $schedule): Schedule
    {
        $instance = new static(static::getScheduleTimezone());
        $instance->events = $schedule->events();
        return $instance;
    }

    private static function getScheduleTimezone()
    {
    	$config = resolve('config');
        return $config->get('app.schedule_timezone', $config->get('app.timezone'));
    }
}
