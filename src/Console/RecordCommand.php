<?php

namespace Cronboard\Console;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Discovery\DiscoverCommandsAndTasks;
use Cronboard\Support\Environment;
use Illuminate\Console\Command;

class RecordCommand extends Command
{
    use Concerns\HasAccessToCronboard;
    use Concerns\ValidatesCronboardConfiguration;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronboard:record';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Record commands to Cronboard';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        if (! $this->isCronboardEnabled()) {
            $this->error('Cronboard is disabled. Please enable it in `config\\cronboard.php` to record your schedule.');
            return 0;
        }

        if (! $this->validateCronboardConfiguration()) {
            return 1;
        }

        // force refresh of commands
        $snapshot = (new DiscoverCommandsAndTasks($this->laravel))->getNewSnapshotAndStore();

        $endpoint = $this->laravel
            ->make(Client::class)
            ->cronboard();

        // exceptions are handled here because we want to notify users about any issues during recording
        $response = $endpoint->record(
            $snapshot->getCommands(),
            $snapshot->getTasksByKey(),
            (new Environment($this->laravel))->toArray()
        );

        $success = $response['success'] ?? false;

        if ($success) {
            $this->info('Schedule recorded!');
            return 0;
        } else {
            $this->error('Could not record schedule.');
            return 1;
        }
    }
}
