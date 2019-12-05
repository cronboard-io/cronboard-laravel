<?php

namespace Cronboard\Core\Execution\Collectors;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use ReflectionClass;

class Collector implements Arrayable
{
    protected $collectors;

    public function __construct(array $collectors = [])
    {
        $this->collectors = Collection::wrap($collectors);
    }

    public function getKey(): string
    {
        if (! empty($this->collectors)) {
            return 'compound';
        }
        $shortName = (new ReflectionClass($this))->getShortName();
        return strtolower(str_replace('Collector', '', $shortName));
    }

    public function start()
    {
        $this->collectors->each->start();
    }

    public function end()
    {
        $this->collectors->each->end();
    }

    public function toArray(): array
    {
        return $this->collectors->keyBy->getKey()->toArray();
    }
}
