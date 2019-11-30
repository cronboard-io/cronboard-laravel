<?php

namespace Cronboard\Tests\Core\Discovery;

use Cronboard\Core\Discovery\DiscoverTasksViaScheduler;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Tests\Stubs\ConfigurableConsoleKernel;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Stubs\CronboardTestInvokable;
use Cronboard\Tests\Stubs\CronboardTestJob;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;

class DiscoverTasksViaSchedulerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        
        $this->kernel = $this->app->make(Kernel::class);
    }

    protected function resolveApplicationConsoleKernel($app)
    {
        $app->singleton(Kernel::class, ConfigurableConsoleKernel::class);
    }

    /** @test */
    public function it_can_discover_command_by_class_name()
    {
        ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->command(CronboardTestCommand::class, [3])->everyFiveMinutes();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
    	$this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getType(), 'command');
        $this->assertEquals($command->getAlias(), 'cb:test');
        $this->assertEquals($command->getHandler(), CronboardTestCommand::class);

        $this->assertCount(5, $task->getParameters()->getGroupParameters(Parameters::GROUP_CONSOLE));

        // validate number of constraints
        $this->assertCount(1, $task->getConstraints());

        // validate constraints
        $this->assertEquals($task->getConstraints()[0], [
            'everyFiveMinutes', []
        ]);
    }

    /** @test */
    public function it_can_discover_command_by_alias()
    {
        ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->command("cb:test 3")->everyFiveMinutes();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getAlias(), 'cb:test');
    }

    /** @test */
    public function it_can_discover_command_by_alias_and_parameters()
    {
        ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->command("cb:test 3 --commandBooleanOption", [4])->everyFiveMinutes();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getAlias(), 'cb:test');

        $taskParameters = $task->getParameters();
        
        $taskConsoleParameters = $taskParameters->getGroupParameters(Parameters::GROUP_CONSOLE);

        $this->assertEquals($taskConsoleParameters->count(), 5);
        $this->assertEquals($taskConsoleParameters->getParameterByName('commandBooleanOption')->getValue(), true);
        $this->assertTrue($taskConsoleParameters->getParameterByName('commandArgument')->getValue() == 3);
        $this->assertTrue($taskConsoleParameters->getParameterByName('commandOptionalArgument')->getValue() == 4);

        // if not explicitly provided we do not set it to the default
        $this->assertNull($taskConsoleParameters->getParameterByName('commandArgumentWithDefault')->getValue());
        $this->assertNull($taskConsoleParameters->getParameterByName('commandOption')->getValue());
        
        $this->assertEquals($command->getAlias(), 'cb:test');
    }

    /** @test */
    public function it_can_discover_job_by_class_name()
    {
        ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->job(CronboardTestJob::class)->everyFiveMinutes();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getType(), 'job');
        $this->assertEquals($command->getHandler(), CronboardTestJob::class);
    }

    /** @test */
    public function it_can_discover_callback()
    {
    	ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->call(function () {
                echo "Test";
            })->weekly();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getType(), 'closure');
        $this->assertEquals($command->getHandler(), 'Closure');
    }

    /** @test */
    public function it_can_discover_invokable_by_class_name()
    {
        ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->call(CronboardTestInvokable::class)->weekly()->mondays();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getType(), 'invokable');
        $this->assertEquals($command->getHandler(), CronboardTestInvokable::class);

        $this->assertEquals($task->getConstraints(), [
            ['weekly', []],
            ['mondays', []]
        ]);
    }

    /** @test */
    public function it_can_discover_invokable_by_instance()
    {
    	ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->call(new CronboardTestInvokable)->weekly()->mondays();
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getType(), 'invokable');
        $this->assertEquals($command->getHandler(), CronboardTestInvokable::class);
    }

    /** @test */
    public function it_can_discover_exec()
    {
    	ConfigurableConsoleKernel::modifySchedule(function($schedule){
            $schedule->exec('npm -v')->dailyAt(3);
        });

        $action = new DiscoverTasksViaScheduler($this->app);
        $tasks = $action->getTasks();
        $this->assertEquals($tasks->count(), 1);

        $task = $tasks->first();

        $command = $task->getCommand();
        $this->assertEquals($command->getType(), 'exec');
        $this->assertEquals($command->getHandler(), 'npm -v');

        $this->assertEquals($task->getConstraints(), [
            ['dailyAt', [3]],
        ]);
    }
}