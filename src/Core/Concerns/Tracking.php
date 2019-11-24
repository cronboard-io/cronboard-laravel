<?php

namespace Cronboard\Core\Concerns;

use Closure;
use Cronboard\Core\Discovery\Schedule\Recorder;
use Cronboard\Core\Schedule;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;

trait Tracking
{
    public function extend(LaravelSchedule $schedule): LaravelSchedule
    {
        if ($schedule instanceof Recorder) {
            return $schedule;
        }

        $this->ensureHasBooted();

        return $this->getCronboardScheduleInstance();
    }

    public function dontTrack(LaravelSchedule $schedule, Closure $scheduleGroup = null): LaravelSchedule
    {
        if (! is_null($scheduleGroup)) {
            $scheduleGroup($schedule);
        }
        return $schedule;
    }

    private function getCronboardScheduleInstance(): Schedule
    {
        static $instance = null;

        if (empty($instance)) {
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
