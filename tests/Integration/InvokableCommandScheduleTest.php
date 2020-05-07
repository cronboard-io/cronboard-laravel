<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Api\Endpoints\Tasks;
use Cronboard\Core\Cronboard;
use Cronboard\Tests\Integration\Commands\InvokableCommand;
use Cronboard\Tests\Stubs\ConfigurableConsoleKernel;
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

        // get events
        $events = $this->schedule->dueEvents($this->app);

        // make sure tasks are loaded in Cronboard
        $this->loadTasksIntoCronboard();
        $cronboard = $this->app->make(Cronboard::class);

        $this->assertEquals(1, $events->count());
        $this->assertEquals(1, $cronboard->getTasks()->count());
        $task = $cronboard->getTasks()->first();

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

        $events[0]->run($this->app);
    }
}