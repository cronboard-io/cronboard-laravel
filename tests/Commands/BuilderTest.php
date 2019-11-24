<?php

namespace Cronboard\Tests\Commands;

use Cronboard\Commands\Builder;
use Cronboard\Commands\Command;
use Cronboard\Commands\CommandByAlias;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Stubs\CronboardTestInvokable;
use Cronboard\Tests\Stubs\CronboardTestJob;
use Cronboard\Tests\TestCase;

class BuilderTest extends TestCase
{
	protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new Builder($this->app);
    }

    /** @test */
    public function it_creates_command_from_command_class()
    {
    	$command = $this->builder->fromClass(CronboardTestCommand::class);
    	
    	$this->assertEquals($command->getType(), 'command');
    	$this->assertEquals($command->getHandler(), CronboardTestCommand::class);
    	$this->assertEquals($command->getAlias(), 'cb:test');
    }

    /** @test */
    public function it_creates_command_from_job_class()
    {
    	$command = $this->builder->fromClass(CronboardTestJob::class);
    	
    	$this->assertEquals($command->getType(), 'job');
    	$this->assertEquals($command->getHandler(), CronboardTestJob::class);
    }

    /** @test */
    public function it_creates_command_from_invokable_class()
    {
    	$command = $this->builder->fromClass(CronboardTestInvokable::class);
    	
    	$this->assertEquals($command->getType(), 'invokable');
    	$this->assertEquals($command->getHandler(), CronboardTestInvokable::class);
    }

    /** @test */
    public function it_creates_command_from_closure_class()
    {
    	$command = $this->builder->fromClass('Closure');
    	
    	$this->assertEquals($command->getType(), 'closure');
    	$this->assertEquals($command->getHandler(), 'Closure');
    }

    /** @test */
    public function it_creates_command_from_object()
    {
    	$object = $this->app->make(CronboardTestCommand::class);
    	$command = $this->builder->fromObject($object);
    	
    	$this->assertEquals($command->getType(), 'command');
    	$this->assertEquals($command->getHandler(), CronboardTestCommand::class);
    	$this->assertEquals($command->getAlias(), 'cb:test');
    }

    /** @test */
    public function it_creates_command_from_scheduler_command()
    {
    	$callable = $this->callable('command', [CronboardTestCommand::class]);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertTrue($command instanceof Command);

    	$this->assertEquals($command->getType(), 'command');
    	$this->assertEquals($command->getHandler(), CronboardTestCommand::class);
    	$this->assertEquals($command->getAlias(), 'cb:test');
    }

    /** @test */
    public function it_creates_command_from_scheduler_alias()
    {
    	$callable = $this->callable('command', ['cb:test']);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertTrue($command instanceof CommandByAlias);

    	$this->assertEquals($command->getType(), 'command');
    	$this->assertEquals($command->getHandler(), 'cb:test');
    	$this->assertEquals($command->getAlias(), 'cb:test');
    }

    /** @test */
    public function it_creates_command_from_scheduler_exec()
    {
    	$callable = $this->callable('exec', ['npm -v']);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertEquals($command->getType(), 'exec');
    	$this->assertEquals($command->getHandler(), 'npm -v');
    }

    /** @test */
    public function it_creates_command_from_scheduler_job()
    {
    	$callable = $this->callable('job', [CronboardTestJob::class]);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertEquals($command->getType(), 'job');
    	$this->assertEquals($command->getHandler(), CronboardTestJob::class);
    }

    /** @test */
    public function it_creates_command_from_scheduler_call_invokable_class()
    {
    	$callable = $this->callable('call', [CronboardTestInvokable::class]);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertEquals($command->getType(), 'invokable');
    	$this->assertEquals($command->getHandler(), CronboardTestInvokable::class);
    }

    /** @test */
    public function it_creates_command_from_scheduler_call_invokable_instance()
    {
    	$callable = $this->callable('call', [new CronboardTestInvokable]);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertEquals($command->getType(), 'invokable');
    	$this->assertEquals($command->getHandler(), CronboardTestInvokable::class);
    }

    /** @test */
    public function it_creates_command_from_scheduler_call_closure()
    {
    	$callable = $this->callable('call', [function(){
    		echo 'Test';
    	}]);
    	$command = $this->builder->fromScheduler($callable);

    	$this->assertEquals($command->getType(), 'closure');
    	$this->assertEquals($command->getHandler(), 'Closure');
    }

    /** @test */
    public function it_creates_command_from_scheduler_alias_with_parameters()
    {
        $callable = $this->callable('command', ['cb:test --commandBooleanOption']);
        $command = $this->builder->fromScheduler($callable);

        $this->assertTrue($command instanceof CommandByAlias);

        $this->assertEquals($command->getType(), 'command');
        $this->assertEquals($command->getHandler(), 'cb:test');
        $this->assertEquals($command->getAlias(), 'cb:test');
    }

    protected function callable($method, $args)
    {
    	return compact('method', 'args');
    }
}