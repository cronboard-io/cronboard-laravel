<?php

namespace Cronboard\Tests\Commands;

use Cronboard\Commands\Builder;
use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\Inspectors\ConsoleCommandInspector;
use Cronboard\Core\Reflection\Inspectors\InvokableCommandInspector;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Stubs\CronboardTestInvokable;
use Cronboard\Tests\Stubs\CronboardTestJob;
use Cronboard\Tests\TestCase;

class CommandTest extends TestCase
{
    /** @test */
    public function it_returns_console_inspector_for_console_command()
    {
        $command = (new Builder($this->app))->fromClass(CronboardTestCommand::class);
        $this->assertInstanceOf(ConsoleCommandInspector::class, $command->getInspector());

        $command = (new Builder($this->app))->fromClass(CronboardTestJob::class);
        $this->assertInstanceOf(Inspector::class, $command->getInspector());
        $this->assertNotInstanceOf(ConsoleCommandInspector::class, $command->getInspector());
        $this->assertNotInstanceOf(InvokableCommandInspector::class, $command->getInspector());

        $command = (new Builder($this->app))->fromClass(CronboardTestInvokable::class);
        $this->assertInstanceOf(InvokableCommandInspector::class, $command->getInspector());
    }

}