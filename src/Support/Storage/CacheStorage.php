<?php

namespace Cronboard\Support\Storage;

use Illuminate\Contracts\Cache\Factory;

class CacheStorage implements Storage
{
    protected $cache;

    public function __construct(Factory $cache)
    {
        $this->cache = $cache;
    }

    public function store(string $key, $value)
    {
        $this->cache->forever($key, $value);
    }

    public function get(string $key, $default = null)
    {
        return $this->cache->get($key, $default);
    }

    public function remove(string $key)
    {
        return $this->cache->forget($key);
    }

    public function empty()
    {
        // TODO: remove all storage
    }
}
