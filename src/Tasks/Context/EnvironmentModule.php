<?php

namespace Cronboard\Tasks\Context;

use Cronboard\Support\Environment;
use Illuminate\Contracts\Container\Container;

class EnvironmentModule extends Module
{
	protected $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	public function load(array $data)
	{
		//
	}

	public function toArray(): array
	{
		return [
			//
		];
	}

	public function getHooks(): array
	{
		return [
			'getEnvironment',
		];
	}

	public function getEnvironment(): Environment
    {
        return new Environment($this->container);
    }
}
