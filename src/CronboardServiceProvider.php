<?php

namespace Cronboard;

use Cronboard\Console\InstallCommand;
use Cronboard\Console\PreviewCommand;
use Cronboard\Console\RecordCommand;
use Cronboard\Console\StatusCommand;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Configuration;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Core\LoadRemoteTasksIntoSchedule;
use Cronboard\Core\Schedule as CronboardSchedule;
use Cronboard\Facades\Cronboard as CronboardFacade;
use Cronboard\Runtime;
use Cronboard\Support\FrameworkInformation;
use Cronboard\Support\Helpers;
use Cronboard\Support\QueueDispatcherWrapper;
use Cronboard\Support\Signing\Verifier;
use Cronboard\Support\Storage\CacheStorage;
use Cronboard\Support\Storage\Storage;
use Cronboard\Support\TrackedEventMixin;
use Cronboard\Support\TrackedScheduleMixin;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskEventSubscriber;
use Illuminate\Bus\Dispatcher;
use Illuminate\Console\Events\ArtisanStarting;
use Illuminate\Console\Scheduling\Event as SchedulerEvent;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Contracts\Bus\Dispatcher as DispatcherContract;
use Illuminate\Contracts\Bus\QueueingDispatcher as QueueingDispatcherContract;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\Macroable;

class CronboardServiceProvider extends ServiceProvider
{
    use FrameworkInformation;

    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/cronboard.php' => config_path('cronboard.php'),
            ], 'config');

            $this->commands([
                RecordCommand::class,
                PreviewCommand::class,
                StatusCommand::class,
                InstallCommand::class,
            ]);

            Queue::before(function(JobProcessing $event) {
                $this->app['cronboard']->reboot();
            });

            Event::listen(ArtisanStarting::class, function() {
                $this->app['cronboard']->boot();
            });

            Event::subscribe(TaskEventSubscriber::class);

            SchedulerEvent::mixin(new TrackedEventMixin);
            CronboardSchedule::mixin(new TrackedScheduleMixin);

            if (Helpers::usesTrait(Schedule::class, Macroable::class)) {
                Schedule::mixin(new TrackedScheduleMixin);    
            }

            $this->addTrackingToQueueDispatcher($this->app);

            $this->app->resolving(Schedule::class, function ($schedule) {
                if ($this->isCronboardActive()) {
                    $this->app['cronboard']->trackSchedule($schedule);
                }
            });
        }

        // if cache has been cleared - make sure we refresh the snapshot
        Event::listen('cache:cleared', function() {
            if ($this->isCronboardActive()) {
                (new DiscoverCommandsAndTasks($this->app))->getNewSnapshotAndStore();
            }
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cronboard.php', 'cronboard');

        $this->registerConfiguration($this->app);

        $this->registerStorage($this->app);

        $this->registerClient($this->app);

        $this->registerResponseVerifier($this->app);

        $this->registerCronboard($this->app);

        $this->registerScheduledTaskTracking($this->app);

        $this->registerExceptionHandler($this->app);

        $this->registerCollectionExtensions($this->app);
    }

    /**
     * Register the Cronboard configuration
     * @param  Container $app
     * @return void
     */
    public function registerConfiguration(Container $app)
    {
        $configuration = new Configuration($app, $app->config['cronboard']);
        $app->instance(Configuration::class, $configuration);

        return $this;
    }

    /**
     * Register the Cronboard storage
     * @param  Container $app
     * @return void
     */
    public function registerStorage(Container $app)
    {
        if (! $app->environment('testing')) {
            $app->singleton(Storage::class, CacheStorage::class);
        }

        return $this;
    }

    /**
     * Register an API response verifier
     * @param  Container $app
     * @return void
     */
    public function registerResponseVerifier(Container $app)
    {
        $app->instance(Verifier::class, $app->make(Verifier::class));
    }

    /**
     * Register the Cronboard client.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    public function registerClient(Container $app)
    {
        $app->instance(Client::class, $app->make(Client::class));
    }

    /**
     * Register the Cronboard.
     *
     * @param \Illuminate\Contracts\Container\Container $app
     *
     * @return void
     */
    public function registerCronboard(Container $app)
    {
        $cronboard = $app->make(Cronboard::class);
        $app->instance('cronboard', $cronboard);
        $app->alias('cronboard', Cronboard::class);

        $app->bind('cronboard.runtime', function($app) {
            return $app->make(Runtime::class);
        });

        $loader = AliasLoader::getInstance();
        $loader->alias('Cronboard', CronboardFacade::class);
    }

    /**
     * Register tracking
     * @param  Container $app
     * @return void
     */
    public function registerScheduledTaskTracking(Container $app)
    {
        $app->extend(Schedule::class, function($schedule) {
            if ($this->app['cronboard']->ready()) {
                return $this->trackScheduledTasks($schedule);
            }
            return $schedule;
        });
    }

    /**
     * Add tracking to queue dispatcher
     * @param  Container $app
     * @return void
     */
    public function addTrackingToQueueDispatcher(Container $app)
    {
        $app->instance(Dispatcher::class, new QueueDispatcherWrapper($app[Dispatcher::class]));

        $this->app->alias(
            Dispatcher::class, DispatcherContract::class
        );

        $this->app->alias(
            Dispatcher::class, QueueingDispatcherContract::class
        );
    }

    /**
     * Register Cronboard as an exception handler
     * @param  Container $app
     * @return void
     */
    public function registerExceptionHandler(Container $app)
    {
        $this->app['events']->listen(MessageLogged::class, function(MessageLogged $event) {
            if ($this->isCronboardActive()) {
                $this->app['cronboard']->handleExceptionEvent($event);
            }
        });
    }

    public function registerCollectionExtensions(Container $app)
    {
        if ($this->getLaravelVersionAsDouble() <= 5.5) {
            Collection::proxy('keyBy');
        }
    }

    protected function trackScheduledTasks(Schedule $schedule)
    {
        $shouldUseCustomSchedule = ! Helpers::usesTrait(Schedule::class, Macroable::class);

        if ($shouldUseCustomSchedule && ! ($schedule instanceof CronboardSchedule)) {
            $schedule = CronboardSchedule::createWithEventsFrom($schedule);
        }

        $this->app['cronboard']->trackSchedule($schedule);

        return $schedule;
    }

    protected function isCronboardActive(): bool
    {
        return $this->app['cronboard']->booted();
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'cronboard',
        ];
    }
}
