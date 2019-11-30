<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Cronboard;
use Cronboard\Support\Testing;
use Cronboard\Tasks\Resolver;
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
        $events = $this->schedule->dueEvents($this->app);
        $this->assertEquals(1, $events->count());

        // make sure tasks are loaded in Cronboard
        $this->loadTasksIntoCronboard();
        $cronboard = $this->app->make(Cronboard::class);

        $commandName = $this->app->make(ConsoleCommand::class)->getName();
        $startEvent = new CommandStarting($commandName, $input = new ArgvInput, $output = new ConsoleOutput);

        $task = $cronboard->getTasks()->first();
        Testing::setCurrentTask($task->getKey());

        // task started
        $this->assertTaskEvent('start', $task);
        // task ended
        $this->assertTaskEvent('end', $task);
        
        $this->tasks->allows('fail');

        $dispatcher = $this->app->make(Dispatcher::class);
        $dispatcher->dispatch($startEvent);

        $endEvent = new CommandFinished($commandName, $input, $output, 0);
        $dispatcher->dispatch($endEvent);

        Testing::setCurrentTask(null);
    }
}