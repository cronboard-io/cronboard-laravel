<?php

namespace Cronboard\Tasks;

class Constraint
{
    protected $name;
    protected $parameters;

    public function __construct(string $name, $parameters = [])
    {
        $this->name = $name;
        $this->parameters = $parameters;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}
