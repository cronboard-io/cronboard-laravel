<?php

namespace Cronboard\Tests\Integration\Commands;

use Cronboard;

class InvokableCommand
{
    public function __invoke(array $report)
    {
    	Cronboard::report(array_keys($report)[0], array_values($report)[0]);
    }
}
