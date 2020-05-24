<?php

namespace Cronboard\Core\Reflection;

use Countable;
use Cronboard\Core\Reflection\Parameters\Parameter;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use IteratorAggregate;

class Parameters implements Arrayable, Countable, IteratorAggregate
{
    const GROUP_CONSTRUCTOR = 'constructor';
    const GROUP_CONSOLE = 'console';
    const GROUP_SCHEDULE = 'schedule';
    const GROUP_INVOKE = 'invoke';

    protected $items;

    public function __construct($items = [])
    {
        $this->items = new Collection($items);
    }

    public static function wrap($value)
    {
        return $value instanceof static
            ? $value
            : new static($value);
    }

    public function fillParameterValuesByOrder(array $values)
    {
        return $this->fillParameterValues($values, false);
    }

    public function fillParameterValues(array $values, bool $byKey = true)
    {
        if (empty($values)) {
            return $this;
        }

        $this->items = $this->fillParameterListWithValues($this->items, $values, $byKey);

        return $this;
    }

    protected function fillParameterListWithValues(Collection $parameters, array $values, bool $byKey)
    {
        if (empty($values)) {
            return $parameters;
        }

        return $parameters->map(function($parameter, $index) use ($values, $byKey) {
            $value = $byKey ? ($values[$parameter->getName()] ?? null) : ($values[$index] ?? null);
            if ($parameter->hasDefault() && $value === $parameter->getDefault()) {
                return $parameter;
            }
            return $parameter->setValue($value);
        });
    }

    public function getParameterByName(string $name): Parameter
    {
        return $this->getItems()->first(function($parameter) use ($name) {
            return $parameter->getName() === $name;
        });
    }

    // public function toArray()
    // {
    //     return array_map(function ($parameters) {
    //         return Collection::wrap($parameters)->toArray();
    //     }, $this->items);
    // }
    
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function toCollection(): Collection
    {
        return $this->getItems();
    }

    public function toArray()
    {
        return $this->items->toArray();
    }

    public function count()
    {
        return $this->items->count();
    }

    public function getIterator()
    {
        return $this->items->getIterator();
    }
}
