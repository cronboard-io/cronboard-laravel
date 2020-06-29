<?php

namespace Cronboard\Support\Storage;

interface Storage
{
    public function store(string $key, $value);
    public function get(string $key, $default = null);
    public function remove(string $key);
    public function empty();
}
