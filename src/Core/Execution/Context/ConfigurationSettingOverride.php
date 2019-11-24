<?php

namespace Cronboard\Core\Execution\Context;

use Cronboard\Core\Execution\Context\Override;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;

class ConfigurationSettingOverride extends Override
{
    public function getType(): string
    {
    	return 'config';
    }

    public function read(Container $container)
    {
    	return $container['config']->get($this->key);
    }

    public function write(Container $container, $value)
    {
    	$container['config']->set($this->key, $value);
    }

    public function normalize(string $key): string
    {
        return strtolower(Str::slug(trim($key), '.'));
    }
}
