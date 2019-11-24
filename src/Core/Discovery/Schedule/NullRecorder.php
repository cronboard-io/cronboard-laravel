<?php

namespace Cronboard\Core\Discovery\Schedule;

use Illuminate\Support\Collection;

class NullRecorder extends Recorder
{
    public function getEventData(): Collection
    {
    	return new Collection;
    }
}
