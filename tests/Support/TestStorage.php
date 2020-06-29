<?php

namespace Cronboard\Tests\Support;

use Cronboard\Support\Storage\Storage;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;

class TestStorage implements Storage
{
    protected $store = [];

    public function store(string $key, $value)
    {
        $this->store[$key] = $value;
    }

    public function get(string $key, $default = null)
    {
        return Arr::get($this->store, $key, $default);
    }

    public function remove(string $key)
    {
        if (array_key_exists($key, $this->store)) {
            unset($this->store[$key]);
        }
    }

    public function empty()
    {
        $this->store = [];
    }
}
