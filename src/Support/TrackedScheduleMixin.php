<?php

namespace Cronboard\Support;

use Cronboard\Core\LoadRemoteTasksIntoSchedule;
use Illuminate\Container\Container;
use Illuminate\Support\Collection;

class TrackedScheduleMixin
{
    public function isReady()
    {
        return function(): bool {
            return isset($this->ready) ? $this->ready : false;
        };
    }

    public function prepare()
    {
        return function(Container $app, bool $refresh = false) {
            if ($refresh) {
                $this->ready = false;
            }

            if (! $this->isReady()) {

                if (! isset($this->applicationEvents)) {
                    $this->applicationEvents = $this->events;
                } else {
                    $this->events = $this->applicationEvents;
                }

                $app->make(LoadRemoteTasksIntoSchedule::class)->execute($this);

                if (! empty($this->events)) {
                    $events = [];

                    $groupedEvents = (new Collection($this->events))->groupBy(function($event) {
                        return $event->isRemoteEvent() ? 'remote' : 'local';
                    });
                    $eventsLocalFirst = $groupedEvents->get('local', new Collection)->merge($groupedEvents->get('remote', new Collection));

                    foreach ($eventsLocalFirst as $event) {
                        $key = $event->getTaskKey() ?: md5(uniqid() . $event->expression);
                        $events[$key] = $event;
                    }

                    $this->events = array_values($events);
                    $this->ready = true;
                }
            }
        };
    }
}
