<?php

namespace Cronboard\Facades;

use Illuminate\Support\Facades\Facade;

class Cronboard extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'cronboard.runtime';
    }
}
