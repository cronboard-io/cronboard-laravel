<?php

namespace Cronboard\Tasks\Context;

class StateModule extends Module
{
    protected $tracking = true;
    protected $active = true;
    protected $once = false;

    public function load(array $data)
    {
        $this->tracking = array_key_exists('tracking', $data) ? $data['tracking'] : true;
        $this->active = array_key_exists('active', $data) ? $data['active'] : true;
        $this->once = array_key_exists('once', $data) ? $data['once'] : false;
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
            'stopTracking',
            'startTracking',
            'isTracked',
            'isActive',
            'shouldExecuteImmediately',
        ];
    }

    public function shouldStoreAfter(string $hookName): bool
    {
        return in_array($hookName, [
            'setOnce', 'setActive', 'setTracking', 'stopTracking', 'startTracking'
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

    public function stopTracking()
    {
        $this->setTracking(false);
    }

    public function startTracking()
    {
        $this->setTracking(true);
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
