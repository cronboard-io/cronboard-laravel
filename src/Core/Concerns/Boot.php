<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Core\Api\Exception;
use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Config\ConfigurationException;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\ExtendSnapshotWithRemoteTasks;
use Illuminate\Console\Command;

trait Boot
{
    protected $config;

    protected $booted = false;
    protected $booting = false;
    protected $offline = false;

    protected $commandsWithRemoteAccess = ['schedule:run', 'schedule:finish'];

    public function ready(): bool
    {
        return $this->config->getEnabled() && $this->config->hasToken();
    }

    public function booted(): bool
    {
        return $this->booted;
    }

    public function boot(): bool
    {
        if ($this->ready() && !$this->booted() && !$this->booting) {
            $this->booting = true;

            // load commands from local codebase
            $snapshot = (new DiscoverCommandsAndTasks($this->app))->getSnapshot();

            try {
                if ($this->shouldContactRemote($snapshot)) {
                    $this->app->make(Configuration::class)->check();
                    $snapshot = (new ExtendSnapshotWithRemoteTasks($this->app))->execute($snapshot);
                }
            } catch (ConfigurationException $exception) {
                $this->reportException($exception);
            }

            $this->loadSnapshot($snapshot);

            $this->loadCurrentTaskContextFromEnvironment();

            $this->booting = false;
            $this->booted = true;

            return true;
        }

        return $this->booted();
    }

    public function allowRemoteAccessForCommand($command)
    {
        $commandToAdd = null;

        if ($command instanceof Command) {
            $commandToAdd = $command->getName();
        } else if (is_string($command)) {
            $commandToAdd = $command;
        }

        if (! is_null($commandToAdd)) {
            $this->commandsWithRemoteAccess[] = $commandToAdd;
        }
    }

    private function shouldContactRemote(Snapshot $snapshot): bool
    {
        if ($this->app->runningInConsole()) {
            $commandName = $_SERVER['argv'][1] ?? null;
            return !empty($commandName) && $this->commandNeedsRemoteTasks($commandName, $snapshot);
        }
        return true;
    }

    private function commandNeedsRemoteTasks(string $commandName, Snapshot $snapshot): bool
    {
        $acceptedConsoleCommands = $snapshot->getCommands()->filter->isConsoleCommand()->map->getAlias();
        $acceptedConsoleCommands = $acceptedConsoleCommands->merge($this->commandsWithRemoteAccess);
        return $acceptedConsoleCommands->contains($commandName);
    }

    private function setOffline(bool $offline)
    {
        $this->offline = $offline;
    }

    protected function ensureHasBooted(): bool
    {
        if (!$this->booted()) {
            return $this->boot();
        }
        return true;
    }

    public function isOffline(): bool
    {
        return $this->offline;
    }

    public function setOfflineDueTo(Exception $e)
    {
        $this->setOffline(true);
        \Log::warning($e->getMessage());
    }
}
