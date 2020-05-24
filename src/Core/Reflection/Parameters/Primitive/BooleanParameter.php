<?php

namespace Cronboard\Core\Reflection\Parameters\Primitive;

use Cronboard\Core\Reflection\Parameters\Parameter;

class BooleanParameter extends Parameter
{
    public function getType(): string
    {
        return 'boolean';
    }
}
