<?php

namespace Cronboard\Tasks;

use Illuminate\Console\Application;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Console\Scheduling\Event;

class TaskKey
{
    public static function createFromEvent(Event $event): ?string
    {
        $from = $event instanceof CallbackEvent ? 'fromCallbackEvent' : 'fromEvent';
        return (new static)->$from($event);
    }

    protected function fromCallbackEvent(CallbackEvent $event): ?string
    {
        return sha1($event->expression . $this->commandBaseFromCallbackEvent($event));
    }

    protected function fromEvent(Event $event): string
    {
        return sha1($event->expression . $this->commandBaseFromEvent($event));
    }

    protected function commandBaseFromCallbackEvent(CallbackEvent $event): ?string
    {
        if (empty($event->description)) {
            // we can't differentiate between callbacks with the same schedule
            // these will get mixed up, unless a name is provided
            return 'Callback';
        } else {
            return $event->description;
        }
    }

    protected function commandBaseFromEvent(Event $event)
    {
        $commandBase = str_replace(Application::phpBinary(), '', $event->command);
        $commandBase = str_replace(Application::artisanBinary(), '', $commandBase);
        return trim($commandBase);
    }
}
