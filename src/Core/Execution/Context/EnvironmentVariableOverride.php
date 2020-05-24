<?php

namespace Cronboard\Core\Execution\Context;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Str;
use PhpOption\None;
use PhpOption\Some;

/*
 * IMPORTANT: Laravel does not read environment variables directly in production
 * instead it caches them in the config, so this might only be useful for
 * custom variables the app might be reading and not caching
 */
class EnvironmentVariableOverride extends Override
{
    public function getType(): string
    {
        return 'env';
    }

    public function read(Container $container)
    {
        if (array_key_exists($this->key, $_ENV)) {
            return Some::create($_ENV[$this->key]);
        }

        return None::create();
    }

    public function write(Container $container, $value)
    {
        if ($value->isDefined()) {
            // Laravel 5.6+
            $_ENV[$this->key] = $value->get();
            // Laravel 5.5
            putenv(implode('=', [$this->key, $value->get()]));
        } else {
            $this->clear();
        }
    }

    public function clear()
    {
        unset($_ENV[$this->key]);
    }

    protected function valueFromArray($value)
    {
        return $value ? Some::create($value) : None::create();
    }

    protected function valueToArray($value)
    {
        return $value->isDefined() ? $value->get() : null;
    }

    public function normalize(string $key): string
    {
        return strtoupper(Str::slug(trim($key), '_'));
    }
}
