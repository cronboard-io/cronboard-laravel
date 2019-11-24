<?php

namespace Cronboard\Tests\Core\Discovery;

use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Discovery\DiscoverCommandsInCodebase;
use Cronboard\Tests\Stubs\ContextRecordInvokable;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Stubs\CronboardTestInvokable;
use Cronboard\Tests\Stubs\CronboardTestJob;
use Cronboard\Tests\TestCase;

class DiscoverCommandsInCodebaseTest extends TestCase
{
    /** @test */
    public function it_discovers_commands_within_folders()
    {
    	$config = $this->app['config']->get('cronboard');
    	$path = realpath(__DIR__ . '/../../Stubs');

        $configuration = new Configuration($this->app, $config);
    	
    	$commands = (new DiscoverCommandsInCodebase($this->app, $configuration))
    		->within([$path])
    		->useBasePath($path)
    		->useRootNamespace('Cronboard\Tests\Stubs\\')
    		->getCommands();
    	
    	$this->assertEquals($commands->count(), 5);

        $commands = $commands->keyBy(function($command){
            return $command->getHandler();
        });

    	$this->assertEquals($commands->get(ContextRecordInvokable::class)->getType(), 'invokable');
        $this->assertEquals($commands->get(CronboardTestInvokable::class)->getType(), 'invokable');
    	$this->assertEquals($commands->get(CronboardTestCommand::class)->getType(), 'command');
    	$this->assertEquals($commands->get(CronboardTestJob::class)->getType(), 'job');
    }
}