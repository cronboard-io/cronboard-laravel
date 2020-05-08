<?php

namespace Cronboard\Tests\Core\Execution;

use Cronboard\Commands\Builder;
use Cronboard\Core\Execution\ExecuteExecTask;
use Cronboard\Tasks\Task;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Mockery as m;

class ExecuteExecTaskTest extends TestCase
{
    private $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $container = Container::getInstance();
        $container->instance(Schedule::class, $this->schedule = m::mock(Schedule::class));
    }

    /** @test */
    public function it_forwards_exec_tasks_to_scheduler()
    {
        $taskKey = 'testTask1';
        $command = (new Builder($this->app))->fromScheduler([
            'method' => 'exec',
            'args' => ['npm -v']
        ]);

        $parameters = $command->getParameters();

        $constraints = [
            ['everyMinute', []]
        ];

        $isCustom = true;

        $task = new Task($taskKey, $command, $parameters, $constraints, $isCustom);

        $action = new ExecuteExecTask($task, $this->app);

        $this->assertEquals('npm -v', $command->getHandler());

        $this->schedule->shouldReceive('exec')->with(m::on(function($handler) use ($command) {
            return $handler === $command->getHandler();
        }), []);

        $action->attach($this->schedule);
    }

    protected function tearDown(): void
    {
        m::close();
    }
}