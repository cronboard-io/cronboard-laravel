<?php

namespace Cronboard\Commands;

use Cronboard\Tasks\Jobs\TrackedJob;

class CommandSupport
{
    public function extend(Command $command)
    {
        if ($command->isJobCommand()) {
            if (! in_array(TrackedJob::class, class_uses_recursive($command->getHandler()))) {
                $command->set('isNotTracked');
            }
        }
        return $command;
    }
}
