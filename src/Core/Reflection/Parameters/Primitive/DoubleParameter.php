<?php

namespace Cronboard\Core\Reflection\Parameters\Primitive;

use Cronboard\Core\Reflection\Parameters\Parameter;

class DoubleParameter extends Parameter
{
	public function getType(): string
	{
		return 'double';
	}
}
