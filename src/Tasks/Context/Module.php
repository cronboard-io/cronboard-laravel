<?php

namespace Cronboard\Tasks\Context;

abstract class Module
{
	abstract public function load(array $data);
	abstract public function toArray(): array;
	abstract public function getHooks(): array;

	public function onContextEnter()
	{
		//
	}

	public function onContextFinalise()
	{
		//
	}

	public function onContextExit()
	{
		//
	}

	public function shouldStoreAfter(string $hookName): bool
	{
		return false;
	}
}
