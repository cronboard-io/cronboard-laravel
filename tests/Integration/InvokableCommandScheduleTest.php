<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Cronboard;
use Cronboard\Tasks\Events\CallbackEvent;
use Cronboard\Tests\Integration\Commands\InvokableCommand;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Mockery as m;

class InvokableCommandScheduleTest extends ScheduleIntegrationTest
{
    protected function modifySchedule(Schedule $schedule): Schedule
    {
        $invokeParameters = [
            'report' => [
                'report' => 'this'
            ]
        ];

        $schedule->call(new InvokableCommand, $invokeParameters)->everyMinute();

        return $schedule;
    }

    /** @test */
    public function it_makes_task_lifecycle_requests_when_task_executes()
    {
        // invokables not supported before Laravel 5.7.3
        if ($this->getLaravelVersionAsInteger() < 5730) {
            $this->assertTrue(true);
            return;
        }

        // make sure tasks are loaded in Cronboard
        $cronboard = $this->loadTasksIntoCronboard();

        // manually boot since we're not going through artisan
        $cronboard->boot();

        // get events
        $events = $this->getSchedule()->dueEvents($this->app);

        $this->assertEquals(1, $events->count());
        $invokableEvent = $events[0];

        $this->assertEquals(1, $cronboard->getTasks()->count());
        $task = $cronboard->getTasks()->first();

        $this->assertEquals($invokableEvent->getTaskKey(), $task->getKey());

        // task queued
        $this->assertTaskEvent('queue', $task);

        // task started
        $this->assertTaskEvent('start', $task);

        // task ended
        $this->assertTaskEvent('end', $task, function($context) {
            // tests if cronboard reporting was passed correctly
            return $context->getReport() == [
                'report' => 'this'
            ];
        });
        
        $this->tasks->allows('fail');

        $invokableEvent->run($this->app);
    }
}