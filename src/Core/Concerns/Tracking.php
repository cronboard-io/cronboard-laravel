<?php

namespace Cronboard\Core\Concerns;

use Closure;
use Cronboard\Core\Discovery\Schedule\Recorder;
use Cronboard\Core\Schedule;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Contracts\Console\Kernel;
use ReflectionClass;

trait Tracking
{
    abstract protected function ensureHasBooted();
    
    public function connect(LaravelSchedule $schedule, bool $unplug = false): LaravelSchedule
    {
        if ($schedule instanceof Recorder) {
            return $schedule;
        }

        $eventsLoaded = false;
        $isDefaultSchedule = get_class($schedule) === LaravelSchedule::class;

        if ($isDefaultSchedule || $unplug) {
            $eventsLoaded = !empty($schedule->events());
        }

        $this->ensureHasBooted();

        $instance = $this->getCronboardScheduleInstance($unplug);
        $cronboardEventsLoaded = !empty($instance->events());

        if ($eventsLoaded && !$cronboardEventsLoaded) {
            $this->loadEventsInSchedule($instance);
        }

        return $instance;
    }

    public function dontTrack(LaravelSchedule $schedule, Closure $scheduleGroup = null): LaravelSchedule
    {
        if (!is_null($scheduleGroup)) {
            $scheduleGroup($schedule);
        }
        return $schedule;
    }

    private function loadEventsInSchedule(Schedule $schedule)
    {
        $consoleKernel = $this->app->make(Kernel::class);
        $scheduleMethod = (new ReflectionClass($consoleKernel))->getMethod('schedule');
        $scheduleMethod->setAccessible(true);
        $scheduleMethod->invoke($consoleKernel, $schedule);
    }

    private function getCronboardScheduleInstance(bool $reset = false): Schedule
    {
        static $instance = null;

        if (empty($instance) || $reset) {
            $instance = (new Schedule($this->getScheduleTimezone()));
            if (method_exists($instance, 'useCache')) {
                return $instance->useCache($this->getScheduleCache());
            }
        }

        return $instance;
    }

    /**
     * Get the timezone that should be used by default for scheduled events.
     *
     * @return \DateTimeZone|string|null
     */
    private function getScheduleTimezone()
    {
        $config = $this->app['config'];

        return $config->get('app.schedule_timezone', $config->get('app.timezone'));
    }

    /**
     * Get the name of the cache store that should manage scheduling mutexes.
     *
     * @return string
     */
    protected function getScheduleCache()
    {
        return $_ENV['SCHEDULE_CACHE_DRIVER'] ?? null;
    }
}
