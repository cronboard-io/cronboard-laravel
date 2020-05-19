<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Api\Endpoints\Tasks;
use Cronboard\Core\Cronboard;
use Cronboard\Tasks\Events\CallbackEvent;
use Cronboard\Tests\Integration\Commands\JobCommand;
use Cronboard\Tests\Integration\Commands\QueuedJobCommand;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Queue\Jobs\DatabaseJob;
use Illuminate\Queue\Worker;
use Illuminate\Queue\WorkerOptions;
use Mockery as m;

class JobCommandScheduleTest extends ScheduleIntegrationTest
{
    // use RefreshDatabase;
    use DatabaseMigrations;

    protected function modifySchedule(Schedule $schedule): Schedule
    {
        $schedule->job(new JobCommand)->everyMinute();
        $schedule->job(new QueuedJobCommand, 'default', 'database')->everyMinute();

        return $schedule;
    }

    /** @test */
    public function it_makes_task_lifecycle_requests_when_task_executes_in_sync()
    {
        // get events
        $events = $this->getSchedule()->dueEvents($this->app);

        // make sure tasks are loaded in Cronboard
        $this->loadTasksIntoCronboard();
        $cronboard = $this->app->make(Cronboard::class);

        $this->assertEquals(2, $events->count());
        $this->assertEquals(2, $cronboard->getTasks()->count());

        $this->assertInstanceOf(CallbackEvent::class, $events[0]);

        $syncTask = $cronboard->getTasks()->values()[0];
        $this->assertTaskEvent('queue', $syncTask);
        $this->assertTaskEvent('start', $syncTask);
        $this->assertTaskEvent('end', $syncTask);
        
        $this->tasks->allows('fail');

        $events[0]->run($this->app);
    }

    /** @test */
    public function it_makes_task_queue_requests_when_task_executes_in_queue()
    {
        $events = $this->getSchedule()->dueEvents($this->app);

        $this->loadTasksIntoCronboard();
        $cronboard = $this->app->make(Cronboard::class);

        $this->assertEquals(2, $events->count());
        $this->assertEquals(2, $cronboard->getTasks()->count());

        $this->assertInstanceOf(CallbackEvent::class, $events[1]);

        $queueTask = $cronboard->getTasks()->values()[1];
        $this->assertTaskEvent('queue', $queueTask);
        $this->assertTaskEventNotFired('start', $queueTask);
        $this->assertTaskEventNotFired('end', $queueTask);
        
        $this->tasks->allows('fail');

        $events[1]->run($this->app);
    }

    /** @test */
    public function it_makes_task_lifecycle_requests_when_task_executes_is_processed()
    {
        $events = $this->getSchedule()->dueEvents($this->app);

        $this->loadTasksIntoCronboard();
        $cronboard = $this->app->make(Cronboard::class);

        $this->assertEquals(2, $events->count());
        $this->assertEquals(2, $cronboard->getTasks()->count());

        $queueTask = $cronboard->getTasks()->values()[1];
        $this->assertTaskEvent('queue', $queueTask);
        $this->assertTaskEvent('start', $queueTask);
        $this->assertTaskEvent('end', $queueTask);
        
        $this->tasks->allows('fail');

        // add job to the queue
        $events[1]->run($this->app);

        // process job
        $queueWorker = $this->app->make('queue.worker');
        $queueWorker->runNextJob('database', 'default', new WorkerOptions);
    }

    // https://github.com/laravel/framework/issues/9733#issuecomment-479055459
    protected function tearDown() : void
    {
        $config = app('config');
        parent::tearDown();
        app()->instance('config', $config);
    }
}