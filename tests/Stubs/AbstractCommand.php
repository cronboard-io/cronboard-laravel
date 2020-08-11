<?php

namespace Cronboard\Tests\Stubs;

use Illuminate\Console\Command;

abstract class AbstractCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cb:abstract';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
    }
}
