<?php

namespace Cronboard\Tasks\Context;

class StateModule extends Module
{
    protected $tracking = true;
    protected $active = true;
    protected $once = false;

    public function load(array $data)
    {
        $this->tracking = $data['tracking'] ?? true;
        $this->active = $data['active'] ?? true;
        $this->once = $data['once'] ?? false;
    }

    public function toArray(): array
    {
        return [
            'tracking' => $this->tracking,
            'active' => $this->active,
            'once' => $this->once,
        ];
    }

    public function getHooks(): array
    {
        return [
            'setOnce',
            'setActive',
            'setTracking',
            'isTracked',
            'isActive',
            'shouldExecuteImmediately',
        ];
    }

    public function shouldStoreAfter(string $hookName): bool
    {
        return in_array($hookName, [
            'setOnce', 'setActive', 'setTracking'
        ]);
    }

    public function setOnce($once = true)
    {
        $this->once = boolval($once);
    }

    public function setActive($active = true)
    {
        $this->active = boolval($active);
    }

    public function setTracking($tracking = true)
    {
        $this->tracking = boolval($tracking);
    }

    public function isTracked(): bool
    {
        return $this->tracking;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function shouldExecuteImmediately(): bool
    {
        return $this->once;
    }
}
