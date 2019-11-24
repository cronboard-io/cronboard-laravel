<?php

namespace Cronboard\Tasks\Events;

use Cronboard\Core\Execution\Events\TaskFinished;
use Exception;
use Illuminate\Console\Scheduling\CallbackEvent as SchedulingCallbackEvent;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;

class CallbackEvent extends SchedulingCallbackEvent
{
    use TrackedEvent;

    protected $delayedCallback;

    /**
     * Set the human-friendly description of the event.
     *
     * @param  string  $description
     * @return $this
     */
    public function description($description)
    {
        parent::description($description);

        // need to refresh task since its key depends on the callback event description
        if (! empty($this->cronboard)) {
            $task = $this->cronboard->getTaskForEvent($this);
            $this->setTask($task);
        }

        return $this;
    }

    public function setDelayedCallback($callback)
    {
        $this->delayedCallback = $callback;
        return $this;
    }

    /**
     * Run the given event.
     *
     * @param  \Illuminate\Contracts\Container\Container  $container
     * @return mixed
     *
     * @throws \Exception
     */
    public function run(Container $container)
    {
        if (! empty($this->delayedCallback)) {
            $this->callback = function () {
                $callback = $this->delayedCallback;
                $callback($this);
            };
        }

        $executionFailed = false;

        try {
            return parent::run($container);
        } catch (Exception $e) {
            $executionFailed = true;
            $this->cronboard->failWithException($e);
            throw $e;
        } finally {
            if (! $executionFailed) {
                $this->notifyTaskFinished($container->make(Dispatcher::class));
            }
        }
    }
}
