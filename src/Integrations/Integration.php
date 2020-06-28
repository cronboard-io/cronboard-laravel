<?php

namespace Cronboard\Integrations;

use Cronboard\Core\Schedule;
use Illuminate\Contracts\Container\Container;

interface Integration
{
    public function onDueEvents(Schedule $schedule, Container $app);
    public function getAdditionalScheduleCommands(): array;
}
