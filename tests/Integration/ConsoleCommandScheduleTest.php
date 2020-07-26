<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Cronboard;
use Cronboard\Support\Testing;
use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\TaskRuntime;
use Cronboard\Tests\Integration\Commands\ConsoleCommand;
use Cronboard\Tests\Integration\ScheduleIntegrationTest;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Events\Dispatcher;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;

class ConsoleCommandScheduleTest extends ScheduleIntegrationTest
{
    protected function modifySchedule(Schedule $schedule): Schedule
    {
        $schedule->command(ConsoleCommand::class)->everyMinute();
        return $schedule;
    }

    /** @test */
    public function it_makes_task_lifecycle_requests_when_command_events_are_fired_by_scheduler()
    {
        $cronboard = $this->loadTasksIntoCronboard();

        $events = $this->getSchedule()->dueEvents($this->app);
        $this->assertEquals(1, $events->count());

        $commandName = $this->app->make(ConsoleCommand::class)->getName();
        $startEvent = new CommandStarting($commandName, $input = new ArgvInput, $output = new ConsoleOutput);

        $task = $cronboard->getTasks()->first();
        Testing::setCurrentTask($task->getKey());

        $runtime = TaskRuntime::fromTask($task);
        $this->assertTrue($runtime->isTracked());

        // task started
        $this->assertTaskEvent('start', $task)->andReturn([
            'success' => true,
            'key' => $task->getKey()
        ]);
        // task ended
        $this->assertTaskEvent('end', $task);
        
        $this->tasks->allows('fail');

        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->dispatch($startEvent);

        $endEvent = new CommandFinished($commandName, $input, $output, 0);
        $dispatcher->dispatch($endEvent);

        $runtime = TaskRuntime::fromTask($task);
        $this->assertTrue($runtime->isTracked());

        Testing::setCurrentTask(null);
    }

    /** @test */
    public function it_does_not_end_task_if_it_failed_to_start()
    {
        $cronboard = $this->loadTasksIntoCronboard();

        $events = $this->getSchedule()->dueEvents($this->app);
        $this->assertEquals(1, $events->count());

        $commandName = $this->app->make(ConsoleCommand::class)->getName();
        $startEvent = new CommandStarting($commandName, $input = new ArgvInput, $output = new ConsoleOutput);

        $task = $cronboard->getTasks()->first();
        Testing::setCurrentTask($task->getKey());

        $runtime = TaskRuntime::fromTask($task);
        $this->assertTrue($runtime->isTracked());

        // task started
        $this->assertTaskEvent('start', $task)->andReturn([
            'success' => false,
        ]);
        $this->assertTaskEventNotFired('end', $task);

        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->dispatch($startEvent);

        $endEvent = new CommandFinished($commandName, $input, $output, 0);
        $dispatcher->dispatch($endEvent);

        // task should not be tracked if failed to start
        $runtime = TaskRuntime::fromTask($task);
        $this->assertFalse($runtime->isTracked());

        Testing::setCurrentTask(null);
    }
}