<?php

namespace Cronboard\Core\Discovery\Schedule;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Support\Collection;

class EventRecorder
{
    protected $event;
    protected $eventData;
    protected $constraintsRecorder;

    public function __construct(Event $event, array $eventData)
    {
        $this->event = $event;
        $this->eventData = $eventData;
        $this->constraintsRecorder = new ConstraintsRecorder;
    }

    public function __call($method, $args)
    {
        $this->event = call_user_func_array([$this->event, $method], $args);
        $this->constraintsRecorder = call_user_func_array([$this->constraintsRecorder, $method], $args);
        return $this;
    }

    public function getRecordedEvent(): Event
    {
        return $this->event;
    }

    public function getRecordedEventData(): array
    {
        return $this->eventData;
    }

    public function getRecordedConstraints(): array
    {
        return $this->constraintsRecorder->getConstraintsData();
    }

    public function shouldRecord(): bool
    {
        return ! $this->constraintsRecorder->hasRecordedConstraint('doNotTrack');
    }
}
