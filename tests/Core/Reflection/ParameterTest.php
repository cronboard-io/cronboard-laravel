<?php

namespace Cronboard\Tests\Core\Reflection;

use Cronboard\Core\Reflection\GroupedParameters;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\Primitive\StringParameter;
use Cronboard\Tests\TestCase;

class ParameterTest extends TestCase
{
    /** @test */
    public function can_fill_parameters_with_values_by_order()
    {
    	$parameters = [
    		new StringParameter('param1'),
    		new StringParameter('param2'),
    	];

    	$parameters = Parameters::wrap($parameters);
    	$values = [1, 2];

    	$parameters->fillParameterValuesByOrder($values);

    	$array = $parameters->getItems()->toArray();

        $this->assertEquals($array[0]['name'], 'param1');
        $this->assertEquals($array[0]['value'], 1);

        $this->assertEquals($array[1]['name'], 'param2');
        $this->assertEquals($array[1]['value'], 2);
    }

    /** @test */
    public function can_fill_parameters_with_values_by_key()
    {
    	$parameters = [
    		new StringParameter('param1'),
    		new StringParameter('param2'),
    	];

    	$parameters = Parameters::wrap($parameters);
    	$values = [
    		'param2' => 2,
    		'param1' => 1
    	];

    	$parameters->fillParameterValues($values);

    	$array = $parameters->getItems()->toArray();

        $this->assertEquals($array[0]['name'], 'param1');
        $this->assertEquals($array[0]['value'], 1);

        $this->assertEquals($array[1]['name'], 'param2');
        $this->assertEquals($array[1]['value'], 2);
    }

    /** @test */
    public function can_fill_grouped_parameters_with_values_by_key()
    {
    	$parameters = [
    		'group' => [
	    		new StringParameter('param1'),
	    		new StringParameter('param2'),
	    	]
    	];

    	$parameters = GroupedParameters::wrap($parameters);
    	$values = [
    		'param2' => 2,
    		'param1' => 1
    	];

    	$parameters->fillParameterValues($values);

    	$array = $parameters->getItems()->toArray();

        $this->assertEquals($array['group'][0]['name'], 'param1');
        $this->assertEquals($array['group'][0]['value'], 1);

        $this->assertEquals($array['group'][1]['name'], 'param2');
        $this->assertEquals($array['group'][1]['value'], 2);
    }
}