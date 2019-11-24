<?php

namespace Cronboard\Core\Reflection\Parameters\Primitive;

use Cronboard\Core\Reflection\Parameters\Parameter;

class FloatParameter extends Parameter
{
	public function getType(): string
	{
		return 'float';
	}
}
