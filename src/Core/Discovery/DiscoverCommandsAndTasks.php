<?php

namespace Cronboard\Core\Discovery;

use Cronboard\Commands\CommandByAlias;
use Cronboard\Commands\Registry;
use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Discovery\DiscoverCommandsInCodebase;
use Cronboard\Core\Discovery\DiscoverCommandsViaArtisan;
use Cronboard\Core\Discovery\DiscoverTasksViaScheduler;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Support\Composer;
use Illuminate\Contracts\Container\Container;

class DiscoverCommandsAndTasks
{
    use HandlesSnapshotStorage;

    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function getSnapshot(bool $store = true): Snapshot
    {
        $snapshot = $this->loadSnapshot();

        // if snapshot contains invalid information - we force a rebuild
        if (! empty($snapshot) && ! $snapshot->validate()) {
            $snapshot = null;
        }

        if (empty($snapshot)) {
            $snapshot = $this->getNewSnapshot();
            if ($store) {
                $this->storeSnapshot($snapshot);
            }
        }

        return $snapshot;
    }

    public function getNewSnapshot(): Snapshot
    {
        $config = $this->app->make(Configuration::class);

        $commands = $this->app->make(DiscoverCommandsInCodebase::class)
            ->ignoringFiles(Composer::getAutoloadedFiles(base_path('composer.json')))
            ->ignoringPathsOrClasses($config->getDiscoveryIgnores())
            ->getCommands();

        if ($config->shouldDiscoverThirdPartyCommands()) {
            $commandsFromArtisan = $this->app->make(DiscoverCommandsViaArtisan::class)
                ->excludingNamespaces($config->getExcludedThirdPartyCommandNamespaces())
                ->restrictingToNamespaces($config->getRestrictedToThirdPartyCommandNamespaces())
                ->ignoringPathsOrClasses($config->getDiscoveryIgnores())
                ->getCommands();
            $commands = $commands->merge($commandsFromArtisan);
        }

        $commandsAndTasks = $this->app->make(DiscoverTasksViaScheduler::class)
            ->ignoringPathsOrClasses($config->getDiscoveryIgnores())
            ->getCommandsAndTasks();

        $schedulerCommands = $commandsAndTasks['commands']->filter(function($command) {
            return !($command instanceof CommandByAlias);
        });

        // registry removes duplicates from both command sources by user command hashes
        // and restricts set to on project commands
        $registry = new Registry($this->app, $commands->merge($schedulerCommands));
        $commands = $registry->getCommands();

        return new Snapshot($this->app, $commands, $commandsAndTasks['tasks']);
    }

    public function getNewSnapshotAndStore()
    {
        $snapshot = $this->getNewSnapshot();
        $this->storeSnapshot($snapshot);
        return $snapshot;
    }

    protected function loadSnapshot(): ?Snapshot
    {
        $data = $this->getStorage()->get($this->getSnapshotKey());
        if (! empty($data)) {
            return Snapshot::fromArray($this->app, $data);
        }
        return null;
    }
}
