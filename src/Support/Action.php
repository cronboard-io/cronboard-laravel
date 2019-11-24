<?php

namespace Cronboard\Support;

abstract class Action
{
    public function __construct()
    {
        //
    }

    abstract public function execute(array $data = []);

    public function __invoke(array $data = [])
    {
        return $this->execute($data);
    }
}
