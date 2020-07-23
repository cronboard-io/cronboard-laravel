<?php

namespace Cronboard;

use Cronboard\Core\Schedule as CronboardSchedule;
use Cronboard\Support\FrameworkInformation;
use Cronboard\Support\Helpers;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Traits\Macroable;

class CronboardScheduleServiceProvider extends ServiceProvider
{
    use FrameworkInformation;

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register the application services.
     */
    public function register()
    {
        if ($this->app->runningInConsole() && $this->getLaravelVersionAsInteger() < 6000) {
            if ($this->app['cronboard']->ready()) {

                $shouldUseCustomSchedule = ! Helpers::usesTrait(Schedule::class, Macroable::class);
                $isBoundAsInstance = ! array_key_exists(Schedule::class, $this->app->getBindings()) && $this->app->isShared(Schedule::class);

                if ($shouldUseCustomSchedule && $isBoundAsInstance) {
                    $schedule = $this->app[Schedule::class];
                    $schedule = CronboardSchedule::createWithEventsFrom($schedule);

                    $this->app->instance(Schedule::class, $schedule);
                    $this->app->singleton(Schedule::class, function() {
                        return $schedule;
                    });
                }
            }
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            Schedule::class
        ];
    }
}
