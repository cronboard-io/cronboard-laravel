<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Schedule as CronboardSchedule;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;

class ConnectorTest extends TestCase
{
    /** @test */
    public function it_hooks_cronboard_into_schedule()
    {
    	$schedule = $this->app->make(Schedule::class);
        $this->assertEquals(get_class($schedule), CronboardSchedule::class);
    }
}