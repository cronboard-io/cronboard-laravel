<?php

namespace Cronboard\Core\Reflection;

use Illuminate\Support\Collection;

class GroupedParameters extends Parameters
{
    public function __construct($items = [])
    {
        $this->items = Collection::wrap($items)->map(function($parameters, $group){
            return Parameters::wrap($parameters);
        });
    }

    public function setParameterGroup(string $key, Parameters $parameters)
    {
        $this->items[$key] = $parameters;
    }

    public function removeParameterGroup(string $key)
    {
        if (isset($this->items[$key])) {
            unset($this->items[$key]);
        }
    }

    public function fillParameterGroupValuesByOrder(string $group, array $values)
    {
        if (empty($values)) return $this;

        $this->insideParameterGroup($group, function($parameters, $group) use ($values) {
            $this->items[$group] = $parameters->fillParameterValuesByOrder($values);
        });

        return $this;
    }

    public function fillParameterValuesByOrder(array $values)
    {
        throw new \Exception("Cannot fill grouped parameters by order", 1);
    }

    public function getGroupParameters(string $group): Parameters
    {
        return $this->items[$group] ?? new Parameters;
    }

    public function fillParameterValues(array $values, bool $byKey = true)
    {
        if (empty($values)) return $this;

        $this->forEachParameterGroup(function($group, $parameters) use ($values) {
            $this->fillParameterGroupValues($group, $values);
        });

        return $this;
    }

    public function fillParameterGroupValues(string $group, array $values)
    {
        if (empty($values)) return $this;

        $this->insideParameterGroup($group, function($parameters) use ($values) {
            $providedParameters = array_keys($values);
            foreach ($parameters as &$parameter) {
                if (in_array($parameter->getName(), $providedParameters)) {
                    $parameter->setValue($values[$parameter->getName()]);
                }
            }
        });

        return $this;
    }

    protected function insideParameterGroup(string $group, callable $callback)
    {
        $callback($this->items[$group] ?? new Parameters, $group);
    }

    protected function forEachParameterGroup(callable $callback)
    {
        foreach ($this->items as $group => $parameters) {
           $callback($group, $parameters);
        }
    }
}
