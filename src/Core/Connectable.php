<?php

namespace Cronboard\Core;

use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;

interface Connectable
{
    public function connect(LaravelSchedule $schedule, bool $unplug = false): LaravelSchedule;
}
