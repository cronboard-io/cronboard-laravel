<?php

namespace Cronboard\Tests\Core\Reflection;

use Cronboard\Commands\Builder;
use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\ArrayParameter;
use Cronboard\Core\Reflection\Parameters\Input\ArgumentParameter;
use Cronboard\Core\Reflection\Parameters\Input\OptionParameter;
use Cronboard\Core\Reflection\Parameters\ModelParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\BooleanParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\IntegerParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\StringParameter;
use Cronboard\Tests\Stubs\CronboardModel;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Stubs\CronboardTestInvokable;
use Cronboard\Tests\Stubs\CronboardTestJob;
use Cronboard\Tests\TestCase;

class InspectorTest extends TestCase
{
    /** @test */
    public function it_inspects_invokable_objects()
    {
    	$report = $this->getInspectorFor(CronboardTestInvokable::class)->getReport();

    	$this->assertTrue($report->isInstantiable());

    	$parameters = $report->getConstructorParameters()->toCollection();
    	$this->assertCount(1, $parameters);

    	$this->assertInstanceOf(IntegerParameter::class, $parameters[0]);
    	$this->assertEquals($parameters[0]->getName(), 'invokableConstructorParameter');
    	$this->assertEquals($parameters[0]->getType(), 'int');
    	$this->assertEquals($parameters[0]->getDefault(), 5);
    }

    /** @test */
    public function it_inspects_job_objects()
    {
    	$report = $this->getInspectorFor(CronboardTestJob::class)->getReport();

    	$this->assertTrue($report->isInstantiable());

    	$parameters = $report->getConstructorParameters()->toCollection();
    	$this->assertCount(2, $parameters);

    	$this->assertInstanceOf(ModelParameter::class, $parameters[0]);
    	$this->assertEquals($parameters[0]->getName(), 'model');
    	$this->assertEquals($parameters[0]->getClassName(), CronboardModel::class);

    	$this->assertInstanceOf(ArrayParameter::class, $parameters[1]);
    	$this->assertEquals($parameters[1]->getName(), 'options');
    	$this->assertEquals($parameters[1]->getDefault(), []);
    }

    /** @test */
    public function it_inspects_command_objects()
    {
    	$report = $this->getInspectorFor(CronboardTestCommand::class)->getReport();

    	$this->assertTrue($report->isInstantiable());

    	$parameters = $report->getConstructorParameters()->toCollection();
    	$this->assertCount(0, $parameters); // we do not record constructor parameters for console commands

    	// $this->assertInstanceOf(ClassParameter::class, $parameters[0]);
    	// $this->assertEquals($parameters[0]->getName(), 'invokable');
    	// $this->assertEquals($parameters[0]->getType(), 'class');
    	// $this->assertEquals($parameters[0]->getClassName(), CronboardTestInvokable::class);

    	$parameters = $report->getParameterGroup(Parameters::GROUP_CONSOLE)->toCollection();
    	$this->assertCount(5, $parameters);

        $this->assertInstanceOf(ArgumentParameter::class, $parameters[0]);
        $internalParameter = $parameters[0]->getInternalParameter();
        $this->assertInstanceOf(StringParameter::class, $internalParameter);
        $this->assertEquals($internalParameter->getName(), 'commandArgument');
        $this->assertEquals($internalParameter->getType(), 'string');
        $this->assertTrue($internalParameter->getRequired());

        $this->assertInstanceOf(ArgumentParameter::class, $parameters[1]);
        $internalParameter = $parameters[1]->getInternalParameter();
        $parameter = $parameters[1];
        $this->assertInstanceOf(StringParameter::class, $internalParameter);
        $this->assertEquals($parameter->getName(), 'commandOptionalArgument');
        $this->assertEquals($parameter->getType(), 'string');
        $this->assertFalse($parameter->getRequired());

        $this->assertInstanceOf(ArgumentParameter::class, $parameters[2]);
        $internalParameter = $parameters[2]->getInternalParameter();
        $parameter = $parameters[2];
        $this->assertInstanceOf(IntegerParameter::class, $internalParameter);
        $this->assertEquals($parameter->getName(), 'commandArgumentWithDefault');
        $this->assertEquals($parameter->getType(), 'int');
        $this->assertFalse($parameter->getRequired());
        $this->assertTrue($parameter->hasDefault());
        $this->assertEquals($parameter->getDefault(), '5');

        $this->assertInstanceOf(OptionParameter::class, $parameters[3]);
        $internalParameter = $parameters[3]->getInternalParameter();
        $parameter = $parameters[3];
        $this->assertInstanceOf(StringParameter::class, $internalParameter);
        $this->assertEquals($parameter->getName(), 'commandOption');
        $this->assertEquals($parameter->getType(), 'string');
        $this->assertFalse($parameter->getRequired());
        $this->assertTrue($parameter->hasDefault());
        $this->assertEquals($parameter->getDefault(), 'defaultOptionValue');

        $this->assertInstanceOf(OptionParameter::class, $parameters[4]);
        $internalParameter = $parameters[4]->getInternalParameter();
        $parameter = $parameters[4];
        $this->assertInstanceOf(BooleanParameter::class, $internalParameter);
        $this->assertEquals($parameter->getName(), 'commandBooleanOption');
        $this->assertEquals($parameter->getType(), 'boolean');
        $this->assertFalse($parameter->getRequired());
        $this->assertTrue($parameter->hasDefault()); // default is false
    }

    /** @test */
    public function it_cannot_inspect_closures()
    {
    	$inspector = (new Builder($this->app))->fromObject(function($param1){
    		// closure
    	})->getInspector();

    	$report = $inspector->getReport();

    	$this->assertFalse($report->isInstantiable());
    }

    protected function getInspectorFor(string $class): Inspector
    {
    	return (new Builder($this->app))->fromClass($class)->getInspector();
    }
}