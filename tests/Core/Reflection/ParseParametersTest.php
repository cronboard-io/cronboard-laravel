<?php

namespace Cronboard\Tests\Core\Reflection;

use Cronboard\Core\Reflection\GroupedParameters;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\ParseParameters;
use Cronboard\Tests\TestCase;

class ParseParametersTest extends TestCase
{
    /** @test */
    public function can_parse_group_parameters()
    {
    	$parameters = [
    		['name' => 'param1' , 'type' => 'string', 'value' => 'test']
    	];
        $group = ['group' => $parameters];

    	$parameters = (new ParseParameters)->execute($group);

        $this->assertInstanceOf(GroupedParameters::class, $parameters);
        $this->assertEquals($parameters->count(), 1);
        $this->assertArrayHasKey('group', $parameters->getItems());

        $groupParameters = $parameters->getGroupParameters('group');
        $this->assertEquals($groupParameters->count(), 1);
        $parameter = $groupParameters->getItems()[0];

        $this->assertEquals($parameter->getType(), 'string');
        $this->assertEquals($parameter->getValue(), 'test');
        $this->assertEquals($parameter->getName(), 'param1');
    }

    /** @test */
    public function can_parse_parameters()
    {
        $parameters = [
            ['name' => 'param1' , 'type' => 'string', 'value' => 'test']
        ];

        $parameters = (new ParseParameters)->execute($parameters);

        $this->assertInstanceOf(Parameters::class, $parameters);
        $this->assertEquals($parameters->count(), 1);

        $parameter = $parameters->getItems()[0];
        $this->assertEquals($parameter->getType(), 'string');
        $this->assertEquals($parameter->getValue(), 'test');
        $this->assertEquals($parameter->getName(), 'param1');
    }
}