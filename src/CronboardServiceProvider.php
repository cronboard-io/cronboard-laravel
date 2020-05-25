<?php

namespace Cronboard;

use Cronboard\Console\InstallCommand;
use Cronboard\Console\PreviewCommand;
use Cronboard\Console\RecordCommand;
use Cronboard\Console\StatusCommand;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Connector;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Core\Execution\Listeners\CallableEventSubscriber;
use Cronboard\Core\Execution\Listeners\CommandEventSubscriber;
use Cronboard\Core\Execution\Listeners\ExecEventSubscriber;
use Cronboard\Core\Execution\Listeners\JobEventSubscriber;
use Cronboard\Core\LoadRemoteTasksIntoSchedule;
use Cronboard\Facades\Cronboard as CronboardFacade;
use Cronboard\Runtime;
use Cronboard\Support\FrameworkInformation;
use Cronboard\Support\Signing\Verifier;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

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
                $this->app['cronboard']->boot();
            });

            $listeners = [
                JobEventSubscriber::class,
                CommandEventSubscriber::class,
                CallableEventSubscriber::class,
                ExecEventSubscriber::class,
            ];

            // $listeners = [
            //     \Cronboard\Core\Execution\Listeners\DebugEventSubscriber::class
            // ];

            foreach ($listeners as $listener) {
                Event::subscribe($this->app->make($listener));
            }

            $this->hookIntoContainer();
        }

        // if cache has been cleared - make sure we refresh the snapshot
        Event::listen('cache:cleared', function() {
            if ($this->isCronboardActive()) {
                (new DiscoverCommandsAndTasks($this->app))->getNewSnapshotAndStore();
            }
        });
    }

    protected function hookIntoContainer()
    {
        $this->app->resolving(Schedule::class, function($schedule) {
            (new LoadRemoteTasksIntoSchedule($this->app))->execute($schedule);
        });

        if ($this->getLaravelVersionAsInteger() < 5520) {
            // Laravel 5.5
            // force console kernel to add booting callback
            $this->app->make(Kernel::class);

            // add rebinding callback for schedule
            $this->app->booted(function($app) {
                if ($this->isCronboardActive()) {
                    $isBoundAsInstance = !array_key_exists(Schedule::class, $app->getBindings()) && $app->isShared(Schedule::class);
                    if ($isBoundAsInstance) {
                        $app->instance(Schedule::class, $this->connectCronboardSchedule($app[Schedule::class]));
                    }
                }
            });
        }
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/cronboard.php', 'cronboard');

        $this->registerConfiguration($this->app);

        $this->registerClient($this->app);

        $this->registerResponseVerifier($this->app);

        $this->registerCronboard($this->app);

        $this->registerCronboardConnector($this->app);

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
     * Register connector
     * @param  Container $app
     * @return void
     */
    public function registerCronboardConnector(Container $app)
    {
        $app->instance('cronboard.connector', $connector = $app->make(Connector::class));

        // connect when schedule bound as singleton
        $app->extend(Schedule::class, function($schedule) {
            return $this->connectCronboardSchedule($schedule);
        });
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

    protected function connectCronboardSchedule(Schedule $schedule)
    {
        return $this->app['cronboard.connector']->connect($schedule);
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
