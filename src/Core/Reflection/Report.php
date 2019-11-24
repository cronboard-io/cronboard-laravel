<?php

namespace Cronboard\Core\Reflection;

use Cronboard\Core\Reflection\GroupedParameters;
use Cronboard\Core\Reflection\Parameters;
use Illuminate\Support\Collection;

class Report
{
    protected $isInstantiable;
    protected $parameters;

    public function __construct(bool $isInstantiable, Parameters $parameters = null)
    {
        $this->isInstantiable = $isInstantiable;
        $parameters = $parameters ?: new Parameters;
        $this->parameters = GroupedParameters::wrap([
            Parameters::GROUP_CONSTRUCTOR => $parameters
        ]);
    }

    public function isInstantiable(): bool
    {
        return $this->isInstantiable;
    }

    public function getParameters(): GroupedParameters
    {
        return $this->parameters;
    }

    public function addParameterGroup(string $key, $parameters = [])
    {
        $this->parameters->setParameterGroup($key, Parameters::wrap($parameters));
    }

    public function removeParameterGroup(string $key)
    {
        $this->parameters->removeParameterGroup($key);
    }

    public function getParameterGroup(string $key): Parameters
    {
        return $this->parameters->getGroupParameters($key);
    }

    public function getConstructorParameters(): Parameters
    {
        return $this->getParameterGroup(Parameters::GROUP_CONSTRUCTOR);
    }

    public function toArray(): array
    {
        return [
            'isInstantiable' => $this->isInstantiable,
            'parameters' => $this->getParameters()->each->map->toArray()->all(),
        ];
    }
}
