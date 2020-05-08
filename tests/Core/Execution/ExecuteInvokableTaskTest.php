<?php

namespace Cronboard\Tests\Core\Execution;

use Cronboard\Commands\Builder;
use Cronboard\Core\Execution\ExecuteInvokableTask;
use Cronboard\Tasks\Task;
use Cronboard\Tests\Stubs\CronboardTestInvokable;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;
use Mockery as m;

class ExecuteInvokableTaskTest extends TestCase
{
    private $schedule;

    protected function fillParameters(array $parameters, array $values): array
    {
        return Collection::wrap($parameters)->map(function($parametersInGroup) use ($values) {
            return Collection::wrap($parametersInGroup)->map(function($parameter) use ($values) {
                return $parameter->setValue($values[$parameter->getName()] ?? null)->toArray();
            })->toArray();
        })->toArray();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $container = Container::getInstance();
        $container->instance(Schedule::class, $this->schedule = m::mock(Schedule::class));
    }

    /** @test */
    public function it_forwards_invokable_tasks_to_scheduler()
    {
        $taskKey = 'testTask1';
        $command = (new Builder($this->app))->fromClass(CronboardTestInvokable::class);

        $parameters = $command->getParameters();
        $parameters->fillParameterValues([
            'invokableConstructorParameter' => 3,
            'third' => '1 box of waffles'
        ]);

        $constraints = [
            ['everyMinute', []]
        ];

        $isCustom = true;

        $task = new Task($taskKey, $command, $parameters, $constraints, $isCustom);

        $action = new ExecuteInvokableTask($task, $this->app);

        $invokableInstance = $action->getInvokableInstance();
        $scheduleParameters = $action->getInvokeParameters();

        $this->assertEquals($invokableInstance->getConstructorParameter(), 3);
        $this->assertEquals($scheduleParameters, ['third' => '1 box of waffles']);

        $this->schedule->shouldReceive('call')->with(m::on(function($instance) use ($invokableInstance) {
            return $instance->getConstructorParameter() === $invokableInstance->getConstructorParameter();
        }), m::on(function($params) use ($scheduleParameters) {
            return $params === $scheduleParameters;
        }));

        $action->attach($this->schedule);
    }

    protected function tearDown(): void
    {
        m::close();
    }
}