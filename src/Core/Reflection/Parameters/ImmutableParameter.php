<?php

namespace Cronboard\Core\Reflection\Parameters;

use Closure;

class ImmutableParameter extends Parameter
{
	protected $name;
	protected $value;

	public function __construct(string $name, $value = null)
	{
		$this->name = $name;
		$this->value = $value;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getType(): string
	{
		return 'immutable';
	}

	public function isMutable(): bool
	{
		return false;
	}
}
