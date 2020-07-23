<?php

namespace Cronboard\Core\Discovery\Schedule;

use Closure;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Collection;

class Recorder extends Schedule
{
    protected $schedule;
    protected $eventRecorders;

    public function __construct()
    {
        $this->schedule = new Schedule;
        $this->eventRecorders = new Collection;
    }

    public function getEventData(): Collection
    {
        return $this->eventRecorders->filter(function($eventRecorder) {
            return $eventRecorder->shouldRecord();
        })->map(function($eventRecorder) {
            return [
                'event' => $eventRecorder->getRecordedEvent(),
                'eventData' => $eventRecorder->getRecordedEventData(),
                'constraints' => $eventRecorder->getRecordedConstraints()
            ];
        });
    }

    public function getEventRecorders(): Collection
    {
        return $this->eventRecorders;
    }

    /**
     * @param array $args
     */
    protected function record(string $method, array $args)
    {
        $event = call_user_func_array([$this->schedule, $method], $args);
        $eventData = compact('method', 'args');
        return tap(new EventRecorder($event, $eventData), function($eventRecorder) {
            $this->eventRecorders[] = $eventRecorder;
        });
    }

    public function call($callback, array $parameters = [])
    {
        return $this->record('call', func_get_args());
    }

    public function command($command, array $parameters = [])
    {
        return $this->record('command', func_get_args());
    }

    public function job($job, $queue = null, $connection = null)
    {
        return $this->record('job', func_get_args());
    }

    public function exec($command, array $parameters = [])
    {
        return $this->record('exec', func_get_args());
    }

    public function compileConsoleParameters(array $parameters)
    {
        return $this->compileParameters($parameters);
    }
}
