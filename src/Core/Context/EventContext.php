<?php

namespace Cronboard\Core\Context;

use Cronboard\Tasks\TaskKey;
use Illuminate\Console\Scheduling\Event;

class EventContext
{
    protected static $event;

    public static function enter(Event $event)
    {
    	static::$event = $event;
    }

    public static function exit()
    {
    	static::$event = null;
    }

    public static function getEvent(): ?Event
    {
    	return static::$event;
    }
}
