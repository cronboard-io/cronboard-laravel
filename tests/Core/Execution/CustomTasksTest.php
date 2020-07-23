<?php

namespace Cronboard\Tests\Core\Execution;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Exception;
use Cronboard\Core\ExtendSnapshotWithRemoteTasks;
use Cronboard\Core\LoadRemoteTasksIntoSchedule;
use Cronboard\Support\CommandContext;
use Cronboard\Tests\Stubs\Api\SchedulePayload;
use Cronboard\Tests\Stubs\Api\TaskPayload;
use Cronboard\Tests\Stubs\CronboardModel;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;
use Mockery as m;

class CustomTasksTest extends TestCase
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

    protected function loadInSchedule(SchedulePayload $schedule): Schedule
    {
        $endpoint = $this->app->make(Client::class)->mockEndpoint($this->app, 'cronboard');
        $endpoint->shouldReceive('schedule')->andReturn($schedule->toArray());

        $snapshot = new Snapshot($this->app, $schedule->getCommands(), new Collection);
        (new ExtendSnapshotWithRemoteTasks($this->app))->execute($snapshot);

        $this->cronboard->loadSnapshot($snapshot);

        $schedule = $this->app->make(Schedule::class);
        $schedule->prepare($this->app);

        return $schedule;
    }

    /** @test */
    public function it_loads_invokable_tasks_in_schedule()
    {
        $payload = new SchedulePayload;
        $payload->addTask(TaskPayload::invokable());

        $schedule = $this->loadInSchedule($payload);

        $this->assertCount(1, $schedule->events());

        $event = $schedule->events()[0];
        $this->assertTrue($event->isRemoteEvent());
        $this->assertInstanceOf(CallbackEvent::class, $event);
    }

    /** @test */
    public function it_loads_exec_tasks_in_schedule()
    {
        $payload = new SchedulePayload;
        $payload->addTask(TaskPayload::exec());

        $schedule = $this->loadInSchedule($payload);

        $this->assertCount(1, $schedule->events());

        $event = $schedule->events()[0];
        $this->assertTrue($event->isRemoteEvent());
        $this->assertInstanceOf(Event::class, $event);
    }

    /** @test */
    public function it_loads_job_tasks_in_schedule()
    {
        $payload = new SchedulePayload;
        $payload->addTask(TaskPayload::job());

        $schedule = $this->loadInSchedule($payload);

        $this->assertCount(1, $schedule->events());

        $event = $schedule->events()[0];
        $this->assertTrue($event->isRemoteEvent());
        $this->assertInstanceOf(CallbackEvent::class, $event);
    }

    /** @test */
    public function it_loads_command_tasks_in_schedule()
    {
        $payload = new SchedulePayload;
        $payload->addTask(TaskPayload::command());

        $schedule = $this->loadInSchedule($payload);

        $this->assertCount(1, $schedule->events());

        $event = $schedule->events()[0];
        $this->assertTrue($event->isRemoteEvent());
        $this->assertInstanceOf(Event::class, $event);
    }

    /** @test */
    public function it_identifies_model_matching_errors_and_reports_them()
    {
        $cronboard = $this->cronboard;
        $cronboard->shouldReceive('reportException')->with(m::on(function($exceptionClass) {
            return $exceptionClass instanceof Exception;
        }))->once();

        $payload = new SchedulePayload;

        $parameters = [
            [
                "name" => "model",
                "value" => CronboardModel::MISSING_ID,
                "type" => 'model',
                "required" => true,
                "default" => null,
                "id" => uniqid(),
                'class' => CronboardModel::class
            ],
            [
                "name" => "options",
                "value" => null,
                "type" => "array",
                "required" => false,
                "default" => [],
                "id" => uniqid()
            ]
        ];
        $payload->addTask(TaskPayload::job($parameters));

        $schedule = $this->loadInSchedule($payload);

        $this->assertCount(0, $schedule->events());
    }

    protected function tearDown(): void
    {
        m::close();
    }
}