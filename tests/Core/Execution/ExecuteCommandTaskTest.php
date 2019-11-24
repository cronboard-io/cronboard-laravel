<?php

namespace Cronboard\Tests\Inspect;

use Cronboard\Commands\Builder;
use Cronboard\Core\Execution\ExecuteCommandTask;
use Cronboard\Tasks\Task;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Mockery as m;

class ExecuteCommandTaskTest extends TestCase
{
    private $schedule;

    protected function setUp(): void
    {
        parent::setUp();
        $container = Container::getInstance();
        $container->instance(Schedule::class, $this->schedule = m::mock(Schedule::class));
    }

    /** @test */
    public function it_forwards_command_tasks_to_scheduler()
    {
        $taskKey = 'testTask1';
        $command = (new Builder($this->app))->fromClass(CronboardTestCommand::class);

        $parameters = $command->getParameters();
        $parameters->fillParameterValues([
            'commandArgument' => 3,
            'commandBooleanOption' => true
        ]);

        $constraints = [
            ['everyMinute', []]
        ];

        $isCustom = true;

        $task = new Task($taskKey, $command, $parameters, $constraints, $isCustom);

        $action = new ExecuteCommandTask($task, $this->app);

        $commandLineParameters = [
            3, '--commandBooleanOption'
        ];

        $this->assertEquals($commandLineParameters, $action->getCommandLineParameters());

        $this->schedule->shouldReceive('command')->with(m::on(function($handler) use ($command) {
            return $handler === $command->getHandler();
        }), m::on(function($params) use ($commandLineParameters) {
            return $params === $commandLineParameters;
        }));

        $action->attach($this->schedule);
    }

    protected function tearDown(): void
    {
        m::close();
    }
}