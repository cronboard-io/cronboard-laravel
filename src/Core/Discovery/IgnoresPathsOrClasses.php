<?php

namespace Cronboard\Core\Discovery;

use Illuminate\Support\Str;
use SplFileInfo;
use ReflectionClass;

trait IgnoresPathsOrClasses
{
    private $ignoredPathsOrClasses = [];
    private $rootNamespace = '';
    private $basePath = '';

    public function useRootNamespace(string $rootNamespace): self
    {
        $this->rootNamespace = $rootNamespace;
        return $this;
    }

    public function useBasePath(string $basePath): self
    {
        $this->basePath = $basePath;
        return $this;
    }

    public function ignoringPathsOrClasses(array $ignoredPathsOrClasses): self
    {
        $this->ignoredPathsOrClasses = $ignoredPathsOrClasses;
        return $this;
    }

    protected function shouldIgnoreClass(string $class, bool $checkExactClass = false): bool
    {
        if (in_array($class, $this->ignoredPathsOrClasses)) {
            return true;
        }

        if (class_exists($class) && ! $checkExactClass) {
            $filename = (new ReflectionClass($class))->getFileName();
            return $this->shouldIgnorePathName($filename);
        }

        return false;
    }

    protected function shouldIgnoreExactClass(string $class): bool
    {
        return $this->shouldIgnoreClass($class, true);
    }

    protected function shouldIgnorePathName(string $pathname): bool
    {
        foreach ($this->ignoredPathsOrClasses as $pathOrClass) {
            if (Str::startsWith($pathname, $pathOrClass)) {
                return true;
            }
        }
        return false;
    }

    protected function getClassNameFromFile(SplFileInfo $file): string
    {
        $class = trim(str_replace($this->basePath, '', $file->getRealPath()), DIRECTORY_SEPARATOR);

        $class = str_replace(
            [DIRECTORY_SEPARATOR, 'App\\'],
            ['\\', app()->getNamespace()],
            ucfirst(Str::replaceLast('.php', '', $class))
        );

        return $this->rootNamespace . $class;
    }
}
