<?php

namespace Cronboard\Core\Reflection\Parameters\Primitive;

use Cronboard\Core\Reflection\Parameters\Parameter;

class StringParameter extends Parameter
{
    public function getType(): string
    {
        return 'string';
    }
}
