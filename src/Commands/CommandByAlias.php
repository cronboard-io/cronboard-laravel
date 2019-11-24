<?php

namespace Cronboard\Commands;

class CommandByAlias extends Command
{
    public function __construct($type, $alias)
    {
        parent::__construct($type, $alias, $alias);
    }

    public function getAlias(): string
    {
        return $this->getHandler();
    }
}
