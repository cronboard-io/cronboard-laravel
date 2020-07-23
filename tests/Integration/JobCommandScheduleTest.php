<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Cronboard;
use Cronboard\Facades\Cronboard as CronboardFacade;
use Cronboard\Tests\Integration\Commands\JobCommand;
use Cronboard\Tests\Integration\Commands\QueuedJobCommand;
use Cronboard\Tests\Integration\Commands\SyncJobCommand;
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
        $queueJob = new QueuedJobCommand;
        // need to set this for Laravel < 5.7 since scheduler does not support the 3rd param in job()
        $queueJob->connection = 'database';

        $schedule->job(new JobCommand)->everyMinute();
        $schedule->job($queueJob, 'default', 'database')->everyMinute();
        $schedule->job(new SyncJobCommand)->everyMinute();
        

        // test that excluded tasks are present in events but not in tasks
        $schedule->job(new QueuedJobCommand)->everyMinute()->name('skipped')->doNotTrack();

        return $schedule;
    }

    /** @test */
    public function it_makes_task_lifecycle_requests_when_task_executes_in_sync()
    {
        $cronboard = $this->loadTasksIntoCronboard();

        // get events
        $events = $this->getSchedule()->dueEvents($this->app);

        $this->assertEquals(4, $events->count());
        $this->assertEquals(3, $cronboard->getTasks()->count());

        $this->assertTrue($events[0]->isTracked());
        $this->assertNotEmpty($events[0]->getTaskKey());

        $syncTask = $cronboard->getTasks()->values()[0];
        $this->assertTaskEvent('queue', $syncTask);
        $this->assertTaskEvent('start', $syncTask);
        $this->assertTaskEvent('end', $syncTask);
        
        $this->tasks->allows('fail');

        $events[0]->run($this->app);
    }

    /** @test */
    public function it_makes_task_lifecycle_requests_when_task_job_does_not_implement_should_queue()
    {
        $cronboard = $this->loadTasksIntoCronboard();

        // get events
        $events = $this->getSchedule()->dueEvents($this->app);

        $this->assertEquals(4, $events->count());
        $this->assertEquals(3, $cronboard->getTasks()->count());

        $this->assertNotEmpty($events[2]->getTaskKey());

        $syncTask = $cronboard->getTasks()->values()[2];
        $this->assertTaskEvent('queue', $syncTask);
        $this->assertTaskEvent('start', $syncTask);
        $this->assertTaskEvent('end', $syncTask);
        
        $this->tasks->allows('fail');

        $events[2]->run($this->app);
    }

     /** @test */
    public function it_makes_task_queue_requests_when_task_executes_in_queue()
    {
        $cronboard = $this->loadTasksIntoCronboard();

        $events = $this->getSchedule()->dueEvents($this->app);

        $this->assertEquals(4, $events->count());
        $this->assertEquals(3, $cronboard->getTasks()->count());

        $this->assertNotEmpty($events[1]->getTaskKey());

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
        $cronboard = $this->loadTasksIntoCronboard();

        $events = $this->getSchedule()->dueEvents($this->app);

        $this->assertEquals(4, $events->count());
        $this->assertEquals(3, $cronboard->getTasks()->count());

        $queueTask = $cronboard->getTasks()->values()[1];
        $this->assertTaskEvent('queue', $queueTask);
        $this->assertTaskEvent('start', $queueTask);
        $this->assertTaskEvent('end', $queueTask);
        
        $this->tasks->allows('fail');

        // add job to the queue
        $events[1]->run($this->app);

        // process job
        $queueWorker = $this->app->make('queue.worker');
        $queueWorker->runNextJob('database', 'default', $this->getWorkerOptions());
    }

    /** @test */
    public function it_does_not_make_lifecycle_requests_for_untracked_events()
    {
        $cronboard = $this->loadTasksIntoCronboard();

        $events = $this->getSchedule()->dueEvents($this->app);

        $this->assertEquals(4, $events->count());
        $this->assertEquals(3, $cronboard->getTasks()->count());

        $this->assertTaskEventNotFired('queue');
        $this->assertTaskEventNotFired('start');
        $this->assertTaskEventNotFired('end');

        // add job to the queue
        $events[3]->run($this->app);

        // process job
        $queueWorker = $this->app->make('queue.worker');
        $queueWorker->runNextJob('database', 'default', $this->getWorkerOptions());
    }

    protected function getWorkerOptions(): WorkerOptions
    {
        $options = new WorkerOptions;
        $options->sleep = 0;
        return $options;
    }

    // https://github.com/laravel/framework/issues/9733#issuecomment-479055459
    protected function tearDown() : void
    {
        $config = app('config');
        parent::tearDown();
        app()->instance('config', $config);
    }
}