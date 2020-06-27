<?php

namespace Cronboard\Core;

use Closure;
use Cronboard\Core\Exceptions\Exception;
use Cronboard\Core\LoadRemoteTasksIntoSchedule;
use Cronboard\Integrations\Integrations;
use Cronboard\Tasks\Events\CallbackEvent;
use Cronboard\Tasks\Events\Event;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskKey;
use Illuminate\Console\Scheduling\Schedule as LaravelSchedule;
use Illuminate\Container\Container;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Collection;

class Schedule extends LaravelSchedule
{
    protected $insideEventScope = false;

    protected $cronboard;
    protected $app;

    protected $ready;

    public function __construct($timezone = null)
    {
        parent::__construct($timezone);
        $container = Container::getInstance();

        $this->app = $container;
        $this->cronboard = $container['cronboard'];

        $this->ready = false;
    }

    protected function passThroughEventProxy(string $method, array $arguments)
    {
        // we have an active event, which means we're in a nested call
        // we fallback to the default logic
        if ($this->insideEventScope) {
            return parent::$method(...$arguments);
        }

        $this->insideEventScope = true;

        $event = parent::$method(...$arguments);
        // link event callbacks with cronboard
        $event = $this->linkToCronboard($event);

        // we close event scope when done with this event
        $this->insideEventScope = false;

        return $event;
    }

    protected function linkToCronboard($event)
    {
        // remove last event
        array_pop($this->events);

        // wrap with lifecycle events
        $this->events[] = $event = $event->linkToCronboard($this->cronboard);

        return $event;
    }

    public function hasEvents(): bool
    {
        return ! empty($this->events);
    }

    public function resetLoadedEvents()
    {
        $this->events = array_filter($this->events, function($event) {
            return ! $event->isRemoteEvent();
        });

        $this->ready = false;
    }

    protected function prepare(Container $app)
    {
        $this->cronboard->boot();

        if (! $this->ready) {

            (new LoadRemoteTasksIntoSchedule($app))->execute($this);

            if (! empty($this->events)) {
                $events = [];

                $groupedEvents = (new Collection($this->events))->groupBy(function($event) {
                    return $event->isRemoteEvent() ? 'remote' : 'local';
                });
                $eventsLocalFirst = $groupedEvents->get('local', new Collection)->merge($groupedEvents->get('remote', new Collection));

                foreach ($eventsLocalFirst as $event) {
                    $task = $event->loadTaskFromCronboard();
                    $key = empty($task) ? md5(uniqid() . $event->expression) : $task->getKey();
                    $events[$key] = $event;
                }
                $this->events = array_values($events);
                $this->ready = true;
            }
        }
    }

    /**
     * Get all of the events on the schedule that are due.
     *
     * @param  \Illuminate\Contracts\Foundation\Application  $app
     * @return \Illuminate\Support\Collection
     */
    public function dueEvents($app)
    {
        Integrations::onDueEvents($this, $app);
        $this->prepare($app);
        return parent::dueEvents($app);
    }

    /**
     * Get all of the events on the schedule.
     *
     * @return \Illuminate\Console\Scheduling\Event[]
     */
    public function events()
    {
        $this->prepare($this->app);
        return parent::events();
    }

    /**
     * Add a new callback event to the schedule.
     *
     * @param  string|callable  $callback
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function call($callback, array $parameters = [])
    {
        $this->events[] = $event = new CallbackEvent(
            $this->getScheduleEventMutex(), $callback, $parameters
        );

        if ($this->insideEventScope) {
            return $event;
        }

        return $this->linkToCronboard($event);
    }

    /**
     * Add a new command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function exec($command, array $parameters = [])
    {
        if (count($parameters)) {
            $command .= ' ' . $this->compileParameters($parameters);
        }

        $this->events[] = $event = new Event($this->getScheduleEventMutex(), $command, $this->timezone ?? null);

        if ($this->insideEventScope) {
            return $event;
        }

        return $this->linkToCronboard($event);
    }

    /**
     * Get the event mutex
     * @return \Illuminate\Console\Scheduling\Mutex|\Illuminate\Console\Scheduling\EventMutex the mutex
     */
    protected function getScheduleEventMutex()
    {
        return property_exists($this, 'eventMutex') ? $this->eventMutex : $this->mutex;
    }

    /**
     * Add a new Artisan command event to the schedule.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return \Illuminate\Console\Scheduling\Event
     */
    public function command($command, array $parameters = [])
    {
        return $this->passThroughEventProxy('command', func_get_args());
    }

    /**
     * Add a new job callback event to the schedule.
     *
     * @param  object|string  $job
     * @param  string|null  $queue
     * @param  string|null  $connection
     * @return \Illuminate\Console\Scheduling\CallbackEvent
     */
    public function job($job, $queue = null, $connection = null)
    {
        return $this->passThroughEventProxy('job', func_get_args())->setDelayedCallback(function($event) use ($job, $queue, $connection) {
            $job = is_string($job) ? resolve($job) : $job;

            $job = $this->attachTaskToJob($job, $event);

            if ($job instanceof ShouldQueue) {
                dispatch($job)
                    ->onConnection($connection ?? $job->connection)
                    ->onQueue($queue ?? $job->queue);
            } else {
                dispatch_now($job);
            }
        });
    }

    private function attachTaskToJob($job, $event)
    {
        if (method_exists($job, 'setTaskKey')) {
            $task = $event->loadTaskFromCronboard();

            if (!empty($task)) {
                $job->setTaskKey($task->getKey());
            }
        }

        return $job;
    }
}
