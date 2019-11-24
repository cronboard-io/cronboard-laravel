<?php

namespace Cronboard;

use Cronboard\Console\InstallCommand;
use Cronboard\Console\PreviewCommand;
use Cronboard\Console\RecordCommand;
use Cronboard\Console\StatusCommand;
use Cronboard\Core\Api\Client;
use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Cronboard;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Core\Execution\Listeners\CallableEventSubscriber;
use Cronboard\Core\Execution\Listeners\CommandEventSubscriber;
use Cronboard\Core\Execution\Listeners\ExecEventSubscriber;
use Cronboard\Core\Execution\Listeners\JobEventSubscriber;
use Cronboard\Core\LoadRemoteTasksIntoSchedule;
use Cronboard\Facades\Cronboard as CronboardFacade;
use Cronboard\Runtime;
use Cronboard\Support\Signing\Verifier;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Container\Container;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class CronboardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/cronboard.php' => config_path('cronboard.php'),
            ], 'config');

            $this->commands([
                RecordCommand::class,
                PreviewCommand::class,
                StatusCommand::class,
                InstallCommand::class
            ]);
        }

        Queue::before(function (JobProcessing $event) {
            $this->app['cronboard']->boot();
        });

        $listeners = [
            JobEventSubscriber::class,
            CommandEventSubscriber::class,
            CallableEventSubscriber::class,
            ExecEventSubscriber::class,
        ];

        foreach ($listeners as $listener) {
            Event::subscribe($this->app->make($listener));
        }

        $this->bootRemoteTasksIntoSchedule();

        // if cache has been cleared - make sure we refresh the snapshot
        Event::listen('cache:cleared', function () {
            (new DiscoverCommandsAndTasks($this->app))->getNewSnapshotAndStore();
        });
    }

    protected function bootRemoteTasksIntoSchedule()
    {
        $this->app->resolving(Schedule::class, function ($schedule) {
            (new LoadRemoteTasksIntoSchedule($this->app))->execute($schedule);
        });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/cronboard.php', 'cronboard');

        $this->registerConfiguration($this->app);

        $this->registerClient($this->app);

        $this->registerResponseVerifier($this->app);

        $this->registerCronboard($this->app);

        $this->registerExceptionHandler($this->app);

        $this->registerScheduleExtension($this->app);
    }

    /**
     * Register schedule extensions
     * @param  Container $app
     * @return void
     */
    public function registerScheduleExtension(Container $app)
    {
        $app->extend(Schedule::class, function ($laravelSchedule) {
            return $this->app['cronboard']->extend($laravelSchedule);
        });
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

        $app->bind('cronboard.runtime', function($app){
            return $app->make(Runtime::class);
        });

        $loader = AliasLoader::getInstance();
        $loader->alias('Cronboard', CronboardFacade::class);
    }

    /**
     * Register Cronboard as an exception handler
     * @param  Container $app
     * @return void
     */
    public function registerExceptionHandler(Container $app)
    {
        $this->app['events']->listen(MessageLogged::class, function(MessageLogged $event) {
            $this->app['cronboard']->handleExceptionEvent($event);
        });
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
