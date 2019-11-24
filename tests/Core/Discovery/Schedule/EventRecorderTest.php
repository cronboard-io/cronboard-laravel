<?php

namespace Cronboard\Tests\Core\Discovery\Schedule;

use Cronboard\Core\Discovery\Schedule\Recorder;
use Cronboard\Tests\TestCase;
use Illuminate\Support\Str;

class EventRecorderTest extends TestCase
{
    /** @test */
    public function it_schedule_call_constraints()
    {
    	$recorder = new Recorder();

    	$recorder->command('cb:test')->dailyAt(3)->weekends();
    	$eventRecorder = $recorder->getEventRecorders()[0];

		$recordedEvent = $eventRecorder->getRecordedEvent();
		$recordedEventData = $eventRecorder->getRecordedEventData();
		$recordedConstraints = $eventRecorder->getRecordedConstraints();

		$expectedExpression =  '0 3 * * 0,6';
		if (Str::startsWith($this->app->version(), '5.5.')) {
			$expectedExpression .= ' *';
		}

		$this->assertEquals($recordedEvent->expression, $expectedExpression);
		$this->assertEquals($recordedEventData, ['method' => 'command', 'args' => ['cb:test']]);
		$this->assertEquals($recordedConstraints, [
			['method' => 'dailyAt', 'args' => [3]],
			['method' => 'weekends', 'args' => []],
		]);
    }
}