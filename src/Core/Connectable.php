<?php

namespace Cronboard\Core;

use Illuminate\Console\Scheduling\Schedule;

interface Connectable
{
	public function connect(Schedule $schedule, bool $unplug = false): Schedule;
}
