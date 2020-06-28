<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Core\Api\Exception;
use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Config\ConfigurationException;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\ExtendSnapshotWithRemoteTasks;
use Cronboard\Support\CommandContext;
use Cronboard\Integrations\Integrations;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Log;

trait Boot
{
    protected $config;

    protected $booted = false;
    protected $booting = false;
    protected $offline = false;

    protected $commandsWithRemoteAccess = [];

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
        if (! $this->app->runningInConsole()) return true;

        $commandsWithRemoteAccess = $this->getCommandsWithRemoteAccess($snapshot);
        $commandContext = new CommandContext($this->app);

        return $commandContext->inCommandsContext($commandsWithRemoteAccess);
    }

    private function getCommandsWithRemoteAccess(Snapshot $snapshot): Collection
    {
        $acceptedConsoleCommands = $snapshot->getCommands()->filter->isConsoleCommand()->map->getAlias();

        return $acceptedConsoleCommands
            ->merge(['schedule:run', 'schedule:finish'])
            ->merge($this->commandsWithRemoteAccess)
            ->merge(Integrations::getAdditionalScheduleCommands());
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
        Log::warning($e->getMessage());
    }
}
