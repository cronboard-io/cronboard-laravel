<?php

namespace Cronboard\Core\Reflection\Parameters\Primitive;

use Cronboard\Core\Reflection\Parameters\Parameter;

class IntegerParameter extends Parameter
{
	public function getType(): string
	{
		return 'int';
	}
}
