<?php

namespace Cronboard\Support;


class Helpers
{
    public static function implementsInterface(string $class, string $interface): bool
    {
        $implementedInterfaces = class_implements($class);
        return $implementedInterfaces && in_array($interface, array_values($implementedInterfaces));
    }

    public static function usesTrait(string $class, string $trait): bool
    {
        return in_array($trait, class_uses($class));
    }
}
