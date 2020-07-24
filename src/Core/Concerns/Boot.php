<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Core\Configuration;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Core\Discovery\Snapshot;
use Cronboard\Core\Exception as CronboardException;
use Cronboard\Core\ExtendSnapshotWithRemoteTasks;
use Cronboard\Support\CommandContext;
use Cronboard\Tasks\Resolver;
use Exception;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

trait Boot
{
    protected $booted = false;
    protected $booting = false;

    abstract protected function getConfiguration(): Configuration;
    abstract protected function bootErrorHandling();
    abstract protected function reportException(Exception $exception);
    abstract public function loadSnapshot(Snapshot $snapshot);
    abstract public function trackSchedule(Schedule $schedule);

    public function ready(): bool
    {
        return $this->getConfiguration()->getEnabled() && $this->getConfiguration()->hasToken();
    }

    public function booted(): bool
    {
        return $this->booted;
    }

    public function boot(bool $reboot = false): bool
    {
        if ($this->ready() && (! $this->booted || $reboot) && ! $this->booting) {
            $this->booting = true;

            try {
                if (! $reboot) {
                    $this->bootErrorHandling();
                    $this->getConfiguration()->assertTokenFound();
                }

                $this->bootFromSnapshot();
                $this->bootCurrentTaskContext();
            } catch (Exception $e) {
                $this->reportException($e);
            }

            $this->booted = true;
            $this->booting = false;

            $this->bootSchedule($reboot);

            return true;
        }

        return false;
    }

    public function reboot(): bool
    {
        return $this->boot(true);
    }

    private function bootSchedule(bool $reboot = false)
    {
        $schedule = $this->app[Schedule::class];
        $schedule->prepare($this->app, $reboot);
        $this->trackSchedule($schedule);
    }

    private function bootFromSnapshot()
    {
        $discoverAction = new DiscoverCommandsAndTasks($this->app);
        $snapshot = $discoverAction->getSnapshot();

        if ($discoverAction->snapshotWasInvalid()) {
            $this->reportException(new CronboardException('Cronboard snapshot appears to be invalid. Please make sure to run `cronboard:record` after changes to your codebase'));
        }

        if ($this->shouldContactRemote($snapshot)) {
            $snapshot = (new ExtendSnapshotWithRemoteTasks($this->app))->execute($snapshot);
        }

        $this->loadSnapshot($snapshot);
    }

    private function bootCurrentTaskContext()
    {
        $key = Resolver::resolveFromEnvironment();
        if ($key) {
            $task = $this->getTasks()->get($key);
            if ($task) {
                TaskContext::enter($task);
            }
        }
    }

    private function shouldContactRemote(Snapshot $snapshot): bool
    {
        if (! $this->app->runningInConsole()) return true;

        $commandContext = new CommandContext($this->app);

        $commandsWithRemoteAccess = $snapshot->getCommands()->filter->isConsoleCommand()->map->getAlias()
            ->merge($commandContext->getSchedulerContextCommands())
            ->merge($commandContext->getQueueWorkerContextCommands());

        return $commandContext->inCommandsContext($commandsWithRemoteAccess);
    }
}
