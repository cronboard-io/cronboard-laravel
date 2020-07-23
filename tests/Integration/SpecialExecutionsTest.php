<?php

namespace Cronboard\Tests\Integration;

use Carbon\Carbon;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\ExtendSnapshotWithRemoteTasks;
use Cronboard\Support\CommandContext;
use Cronboard\Tests\Stubs\Api\SchedulePayload;
use Cronboard\Tests\Stubs\Api\TaskPayload;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Mockery as m;

class SpecialExecutionsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $cronboard = $this->app->make(Cronboard::class);
        $cronboard = m::mock($cronboard);
        $this->app->instance('cronboard', $cronboard);

        // test events are loaded
        $commandContext = m::mock(CommandContext::class);
        $this->app->instance(CommandContext::class, $commandContext);
        
        $commandContext->shouldReceive('inCommandsContext')->andReturn(true);
        $cronboard->shouldReceive('booted')->andReturn(true);

        $this->cronboard = $cronboard;
    }

    /** @test */
    public function it_loads_immediate_executions_cronboard()
    {
        $endpoint = $this->app->make(Client::class)->mockEndpoint($this->app, 'cronboard');
        $schedule = new SchedulePayload;
        $schedule->addQueuedTask(
            $immediate = TaskPayload::immediate(),
            $base = TaskPayload::create()
        );
        $endpoint->shouldReceive('schedule')->andReturn($schedule->toArray());

        $snapshot = new Snapshot($this->app, $schedule->getCommands(), new Collection);
        (new ExtendSnapshotWithRemoteTasks($this->app))->execute($snapshot);

        $cronboard = $this->cronboard;
        $cronboard->loadSnapshot($snapshot);

        // test tasks area loaded
        $this->assertEquals(2, $cronboard->getTasks()->count());

        $baseTask = $cronboard->getTasks()->get($base['key']);
        $this->assertTrue($baseTask->isCronboardTask());
        $this->assertFalse($baseTask->isImmediateTask());
        $this->assertFalse($baseTask->isRuntimeTask());
        $this->assertNull($baseTask->getOriginalTaskKey());

        $immediateTask = $cronboard->getTasks()->get($immediate['key']);
        $this->assertTrue($immediateTask->isCronboardTask());
        $this->assertTrue($immediateTask->isImmediateTask());
        $this->assertTrue($immediateTask->isRuntimeTask());
        $this->assertEquals($immediateTask->getOriginalTaskKey(), $base['key']);

        $schedule = $this->app->make(Schedule::class);
        $schedule->prepare($this->app);

        $this->assertCount(2, $events = $schedule->events());
        $this->assertEquals($events[0]->getTaskKey(), $baseTask->getKey());
        $this->assertEquals($events[1]->getTaskKey(), $immediateTask->getKey());

        // test immediate effect is applied
        Carbon::setTestNow(Carbon::now()->setTime(16, 1, 0));
        $this->assertFalse($events[0]->isDue($this->app));
        $this->assertTrue($events[1]->isDue($this->app));
    }

    /** @test */
    public function it_loads_queued_tasks_in_cronboard()
    {

        $endpoint = $this->app->make(Client::class)->mockEndpoint($this->app, 'cronboard');
        $schedule = new SchedulePayload;
        $schedule->addQueuedTask(
            $queued = TaskPayload::queued(),
            $base = TaskPayload::create()
        );
        $endpoint->shouldReceive('schedule')->andReturn($schedule->toArray());

        $snapshot = new Snapshot($this->app, $schedule->getCommands(), new Collection);
        (new ExtendSnapshotWithRemoteTasks($this->app))->execute($snapshot);

        $cronboard = $this->cronboard;
        $cronboard->loadSnapshot($snapshot);

        // test tasks area loaded
        $this->assertEquals(2, $cronboard->getTasks()->count());

        $baseTask = $cronboard->getTasks()->get($base['key']);
        $this->assertTrue($baseTask->isCronboardTask());
        $this->assertFalse($baseTask->isImmediateTask());
        $this->assertFalse($baseTask->isRuntimeTask());
        $this->assertNull($baseTask->getOriginalTaskKey());

        $immediateTask = $cronboard->getTasks()->get($queued['key']);
        $this->assertTrue($immediateTask->isCronboardTask());
        $this->assertFalse($immediateTask->isImmediateTask());
        $this->assertTrue($immediateTask->isRuntimeTask());
        $this->assertEquals($immediateTask->getOriginalTaskKey(), $base['key']);

        // test events are loaded
        $schedule = $this->app->make(Schedule::class);
        $schedule->prepare($this->app);

        $this->assertCount(1, $events = $schedule->events(), 'Queued task must not be added to the schedule');
    }

    protected function tearDown() : void
    {
        parent::tearDown();
        Carbon::setTestNow();
        m::close();
    }
}