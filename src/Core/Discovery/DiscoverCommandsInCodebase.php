<?php

namespace Cronboard\Core\Discovery;

use Cronboard\Commands\Builder;
use Cronboard\Commands\Command;
use Cronboard\Core\Configuration;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use SplFileInfo;
use Symfony\Component\Finder\Finder;

final class DiscoverCommandsInCodebase
{
    use IgnoresPathsOrClasses;
    
    private $directories = [];
    private $ignoredFiles = [];

    protected $container;

    public function __construct(Container $container, Configuration $config)
    {
        $this->container = $container;
        $this->basePath = $config->getDiscoveryBasePath();
        $this->directories = $config->getDiscoveryPaths();
    }

    public function within(array $directories): self
    {
        $this->directories = $directories;
        return $this;
    }

    public function ignoringFiles(array $ignoredFiles): self
    {
        $this->ignoredFiles = $ignoredFiles;
        return $this;
    }

    public function getCommands(): Collection
    {
        $files = (new Finder())->files()->in($this->directories);
        $builder = new Builder($this->container);

        return collect($files)
            ->reject(function(SplFileInfo $file) {

                $pathname = $file->getPathname();

                if (in_array($pathname, $this->ignoredFiles)) {
                    return true;
                }

                if ($this->shouldIgnorePathName($pathname)) {
                    return true;
                }

                return false;
            })
            ->map(function(SplFileInfo $file) use ($builder) {

                $className = $this->getClassNameFromFile($file);

                if ($this->shouldIgnoreExactClass($className)) {
                    return null;
                }

                return $builder->fromClass($className);
            })
            ->filter()->values();
    }
}
