<?php

namespace Cronboard\Tests\Integration;

use Closure;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Discovery\DiscoverTasksViaScheduler;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Schedule as CronboardSchedule;
use Cronboard\Support\FrameworkInformation;
use Cronboard\Tasks\Task;
use Cronboard\Tests\Stubs\ConfigurableConsoleKernel;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Mockery as m;

abstract class ScheduleIntegrationTest extends TestCase
{
    use FrameworkInformation;
    
    protected $schedule;
    protected $cronboard;
    protected $tasks;

    protected function setUp(): void
    {
        parent::setUp();

        // $this->tasks = $this->app->instance(Tasks::class, $tasks = m::mock(Tasks::class));
        $this->tasks = $this->app->make(Client::class)->mockEndpoint($this->app, 'tasks');

        TaskContext::exit();
    }

    protected function getSchedule()
    {
        $schedule = $this->app->make(Schedule::class);
        $schedule = CronboardSchedule::createWithEventsFrom($schedule);
        return $schedule;
    }

    protected function loadTasksIntoCronboard()
    {
        $commandsAndTasks = (new DiscoverTasksViaScheduler($this->app))->getCommandsAndTasks();
        $tasks = $commandsAndTasks['tasks']->each(function($task){
            $task->setCronboardTask(true);
        });
        $snapshot = new Snapshot($this->app, $commandsAndTasks['commands'], $tasks);

        $this->cronboard = $this->app->make(Cronboard::class);
        $this->cronboard->loadSnapshot($snapshot);

        return $this->cronboard;
    }

    protected function resolveApplicationConsoleKernel($app)
    {
        ConfigurableConsoleKernel::modifySchedule(function($schedule) {
            $this->schedule = call_user_func_array([$this, 'modifySchedule'], [$schedule]);
        });

        $app->singleton(Kernel::class, ConfigurableConsoleKernel::class);
    }

    abstract protected function modifySchedule(Schedule $schedule): Schedule;

    protected function assertTaskEvent(string $event, Task $task = null, Closure $contextArgumentMatcher = null)
    {
        $contextArgument = ! empty($contextArgumentMatcher) ? m::on($contextArgumentMatcher) : m::any();

        return $this->tasks->shouldReceive($event)->with(m::on(function($runtimeTask) use ($task) {
            return empty($task) || $runtimeTask->getKey() === $task->getKey();
        }), $contextArgument)->once();
    }

    protected function assertTaskEventNotFired(string $event, Task $task = null)
    {
        return $this->tasks->shouldNotReceive($event)->with(m::on(function($runtimeTask) use ($task) {
            if ($task) {
                return $runtimeTask->getKey() === $task->getKey();    
            }
            return true;
        }), m::any());
    }
}