<?php

namespace Cronboard\Tests\Integration\Commands;

use Illuminate\Console\Command;
use File;

class ConsoleCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'integration:console';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        File::put(__DIR__ . '/run.log', 'test');
    }
}
