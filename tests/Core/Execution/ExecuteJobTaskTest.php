<?php

namespace Cronboard\Tests\Core\Execution;

use Cronboard\Commands\Builder;
use Cronboard\Core\Execution\ExecuteJobTask;
use Cronboard\Tasks\Task;
use Cronboard\Tests\Stubs\CronboardModel;
use Cronboard\Tests\Stubs\CronboardTestJob;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Mockery as m;

class ExecuteJobTaskTest extends TestCase
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
        $command = (new Builder($this->app))->fromClass(CronboardTestJob::class);

        $parameters = $command->getParameters();

        $parameters->fillParameterValues([
            'model' => 42,
            'options' => ['test' => 1],
            'queue' => 'queueTest',
            'connection' => 'connectionTest'
        ]);

        $constraints = [
            ['everyMinute', []]
        ];
        $task = new Task($taskKey, $command, $parameters, $constraints, $isCustom = true);
        $action = new ExecuteJobTask($task, $this->app);

        $job = $action->getJobInstance();

        // test job was built correctly
        $this->assertEquals($job->getJobModel()->id, 42);
        $this->assertEquals($job->getJobOptions(), ['test' => 1]);

        // test if schedule parameters are set
        $this->schedule->shouldReceive('job')->with(m::on(function($instance) use ($job) {
            return $this->jobMatchesAnother($instance, $job);
        }), 'queueTest', 'connectionTest');

        $action->attach($this->schedule);
    }

    protected function jobMatchesAnother(CronboardTestJob $job1, CronboardTestJob $job2)
    {
        $model1 = $job1->getJobModel();
        $model2 = $job2->getJobModel();
        $modelMatches =  ! empty($model1) && ! empty($model2) && get_class($model1) === get_class($model2) && get_class($model1) === CronboardModel::class;
        $optionsMatch = $job1->getJobOptions() === $job2->getJobOptions();
        return $modelMatches && $optionsMatch;
    }

    protected function tearDown(): void
    {
        m::close();
    }
}