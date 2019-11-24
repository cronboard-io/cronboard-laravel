<?php

namespace Cronboard\Commands\Build;

use Cronboard\Commands\Command;

class FromObject extends FromClass
{
    public function build($object): ?Command
    {
        $className = get_class($object);
        return parent::buildWithInstance($className, $object);
    }
}

