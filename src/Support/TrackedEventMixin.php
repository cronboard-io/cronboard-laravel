<?php

namespace Cronboard\Support;

use Cronboard\Core\Cronboard;
use Cronboard\Tasks\Resolver;
use Cronboard\Tasks\Task;
use Cronboard\Tasks\TaskKey;
use Cronboard\Tasks\TaskRuntime;
use Illuminate\Console\Scheduling\Event;

class TrackedEventMixin
{
    const IMMEDIATE_RUN_EXPRESSION = '* * * * *';

    public function doNotTrack()
    {
    	return function() {
	        $this->doNotTrack = true;
            return $this;
	    };
    }

    public function shouldTrack()
    {
    	return function() {
	        if (! isset($this->doNotTrack)) {
                return true;
            }

            return !! $this->doNotTrack;
	    };
    }

    public function setTracked()
    {
        return function(bool $tracked = true) {
            $this->tracked = $tracked;
        };
    }

    public function isTracked()
    {
        return function(): bool {
            if (! isset($this->tracked)) {
                return false;
            }

            return !! $this->tracked;
        };
    }

    public function setTask()
    {
        return function(Task $task) {
            $this->task = $task->getKey();
        };
    }

    public function linkWithTask()
    {
    	return function(Task $task) {
	        if (! isset($this->originalCommand)) {
	            $this->originalCommand = $this->command;
	        }
	        $this->command = Resolver::ENV_VAR . "=" . $task->getKey() . ' ' . $this->originalCommand;

            $this->setTask($task);

	        $this->recordOutputInTask($task);
	    };
    }

    public function adjustRuntimeData()
    {
        return function(TaskRuntime $runtime) {
            if ($runtime->shouldExecuteImmediately()) {
                $this->originalExpression = $this->expression;
                $this->expression = TrackedEventMixin::IMMEDIATE_RUN_EXPRESSION;
            }
        };
    }

    public function setRemoteEvent()
    {
        return function(bool $remote = true) {
        	$this->remote = $remote;
        };
    }

    public function isRemoteEvent()
    {
        return function() {
        	if (isset($this->remote)) {
        		return $this->remote;
        	}
        	return false;
        };
    }

    public function recordOutputInTask()
    {
        return function(Task $task) {
        	if (method_exists($this, 'ensureOutputIsBeingCaptured')) {
	            $this->ensureOutputIsBeingCaptured(); // Laravel 5.7+
	        } else if (method_exists($this, 'ensureOutputIsBeingCapturedForEmail')) {
	            $this->ensureOutputIsBeingCapturedForEmail(); // Laravel 5.6
	        }

	        $isRecordingOutput = isset($this->recordingOutputInTask) && $this->recordingOutputInTask;

	        if (! $isRecordingOutput) {
	            $this->recordingOutputInTask = true;
	            $event = $this;

                // Note: works only for console commands
	            $this->then(function(Cronboard $cronboard) use ($task, $event) {
	                if (! empty($task)) {
	                    $output = $this->getEventOutput($event);
	                    if (! empty($output)) {
	                        $cronboard->sendTaskOutput($task, $output);
	                    }
	                }
	            });
	        }
        };
    }

    public function getEventOutput()
    {
        return function (Event $event) {
            if (! $event->output ||
                $event->output === $event->getDefaultOutput() ||
                $event->shouldAppendOutput ||
                ! file_exists($event->output)) {
                return '';
            }
            return trim(file_get_contents($event->output));
        };
    }

    public function getTaskKey()
    {
        return function() {
            return $this->task ?? TaskKey::createFromEvent($this);
        };
    }
}
