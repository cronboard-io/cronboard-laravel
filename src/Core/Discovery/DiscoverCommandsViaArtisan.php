<?php

namespace Cronboard\Core\Discovery;

use Cronboard\Commands\Builder;
use Cronboard\Core\Discovery\IgnoresPathsOrClasses;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;

final class DiscoverCommandsViaArtisan
{
    use IgnoresPathsOrClasses;

    protected $container;
    protected $consoleKernel;

    protected $excludeNamespaces = [];
    protected $restrictToNamespaces = [];

    public function __construct(Container $container, Kernel $consoleKernel)
    {
        $this->container = $container;
        $this->consoleKernel = $consoleKernel;
    }

    public function restrictingToNamespaces(array $restrictToNamespaces): self
    {
        $this->restrictToNamespaces = $restrictToNamespaces;
        return $this;
    }

    public function excludingNamespaces(array $excludeNamespaces): self
    {
        $this->excludeNamespaces = $excludeNamespaces;
        return $this;
    }

    public function getCommands(): Collection
    {
        $builder = new Builder($this->container);

        return Collection::wrap($this->consoleKernel->all())
            ->map(function (Command $command) use ($builder) {
                $className = get_class($command);
                if ($this->shouldIgnoreExactClass($className) || ! $this->isClassNamespaceAllowed($className)) {
                    return null;
                }
                return $builder->fromObject($command);
            })
            ->filter()
            ->values();
    }

    protected function isClassNamespaceAllowed(string $className): bool
    {
        if ($this->isClassNamespaceExcluded($className)) {
            return false;
        }

        return $this->isClassInRestrictedNamespaces($className);
    }

    protected function isClassInRestrictedNamespaces(string $className): bool
    {
        if (empty($this->restrictToNamespaces)) return true;
        return Str::startsWith($className, $this->restrictToNamespaces);
    }

    protected function isClassNamespaceExcluded(string $className): bool
    {
        if (empty($this->excludeNamespaces)) return false;
        return Str::startsWith($className, $this->excludeNamespaces);
    }
}
