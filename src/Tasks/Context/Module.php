<?php

namespace Cronboard\Tasks\Context;

abstract class Module
{
    abstract public function load(array $data);
    abstract public function toArray(): array;
    abstract public function getHooks(): array;

    public function onContextEnter(): void
    {
        //
    }

    public function onContextFinalise(): void
    {
        //
    }

    public function onContextExit(): void
    {
        //
    }

    public function shouldStoreAfter(string $hookName): bool
    {
        return false;
    }
}
