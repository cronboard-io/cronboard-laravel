<?php

namespace Cronboard\Console;

use Cronboard\Core\Api\Client;
use Illuminate\Console\Command;

class StatusCommand extends Command
{
    use Concerns\ValidatesCronboardConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronboard:status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get Cronboard status';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->validateCronboardConfiguration()) {
            return 1;
        }

        $info = $this->laravel
            ->make(Client::class)
            ->account()
            ->info();

        $row = [
            'Ready',
            $info['user']['email'] ?? '?',
            $info['project']['name'] ?? '?',
        ];
        $this->table(['Status', 'Account', 'Project'], [$row]);
    }
}
