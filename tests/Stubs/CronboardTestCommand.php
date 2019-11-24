<?php

namespace Cronboard\Tests\Stubs;

use Cronboard;
use Illuminate\Console\Command;
use Log;

class CronboardTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cb:test' . 
        ' {commandArgument} {commandOptionalArgument?} {commandArgumentWithDefault=5 : Example argument description}' . 
        ' {--commandOption=defaultOptionValue : Example option description} {--commandBooleanOption}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CronboardTestInvokable $invokable)
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this->line('Testing with param: ' . $this->argument('param') . ' 2222');

        // $this->line('Testing with param: ' . $this->argument('param') . '1111');
        // sleep(20);

        $this->error('Testing error');

        echo "ECHO test in command";

        Log::info("Log test in command");

        Cronboard::report('CronboardTestCommand', $this->argument('param') ?: 5);
    }
}
