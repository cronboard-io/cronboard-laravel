<?php

namespace Cronboard\Commands;

class CommandNotFoundException extends \Exception
{
    public function __construct(Command $command)
    {
        parent::__construct('Cannot find command: ' . $command->getHandler());
    }
}
