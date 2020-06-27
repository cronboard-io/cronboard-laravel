<?php

namespace Cronboard\Integrations;

use Cronboard\Core\Schedule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

class Integrations
{
	protected static $integrations = [];

	public static function register(Integration $integration)
	{
		static::$integrations[] = $integration;
	}

    public static function onDueEvents(Schedule $schedule, Container $app)
    {
        foreach (static::$integrations as $integration) {
            $integration->onDueEvents($schedule, $app);
        }
    }

    public static function getAdditionalScheduleCommands(): array
    {
        return (new Collection(static::$integrations))
            ->flatMap(function($integration) {
                return $integration->getAdditionalScheduleCommands();
            })
            ->all();
    }
}
