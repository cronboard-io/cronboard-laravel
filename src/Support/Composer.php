<?php

namespace Cronboard\Support;

use Illuminate\Support\Str;

class Composer
{
    public static function getAutoloadedFiles($composerJsonPath): array
    {
        $composerContents = static::getComposerContents($composerJsonPath);

        $paths = array_merge(
            $composerContents['autoload']['files'] ?? [],
            $composerContents['autoload-dev']['files'] ?? []
        );

        $basePath = Str::before($composerJsonPath, 'composer.json');

        return array_map(function(string $path) use ($basePath) {
            return realpath($basePath . $path);
        }, $paths);
    }

    public static function getComposerContents($composerJsonPath): array
    {
        if (!file_exists($composerJsonPath)) {
            return [];
        }

        $composerContents = json_decode(file_get_contents($composerJsonPath), true);

        return $composerContents;
    }
}
