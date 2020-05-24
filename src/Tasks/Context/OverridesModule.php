<?php

namespace Cronboard\Tasks\Context;

use Cronboard\Core\Execution\Context\ParseContext;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class OverridesModule extends Module
{
    protected $overrides;
    protected $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function load(array $data)
    {
        $value = $data['overrides'] ?? null;
        if ($value) {
            $value = $this->formatValueFromStorage($value);
        }
        $this->overrides = $value ?: new Collection;
    }

    public function toArray(): array
    {
        return [
            'overrides' => $this->overrides->toArray()
        ];
    }

    public function getHooks(): array
    {
        return [
            'setOverrides',
            'getExecutionContext'
        ];
    }

    public function shouldStoreAfter(string $hookName): bool
    {
        return $hookName === 'setOverrides';
    }

    public function onContextEnter()
    {
        $this->executeContextOverrides();
    }

    public function onContextExit()
    {
        $this->rollbackContextOverrides();
    }

    public function setOverrides(array $overrides = [])
    {
        $this->overrides = $this->formatValueFromStorage($overrides);
    }

    public function getExecutionContext(): Collection
    {
        return $this->overrides;
    }

    private function formatValueFromStorage($value)
    {
        return (new ParseContext)->execute($value);
    }

    private function executeContextOverrides()
    {
        foreach ($this->overrides as $override) {
            $override->override($this->container);
        }
    }

    private function rollbackContextOverrides()
    {
        foreach ($this->overrides as $override) {
            $override->restore($this->container);
        }
    }
}
