<?php

namespace Cronboard\Tests\Stubs;

use Cronboard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Log;

class ContextRecordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cb:context-record {env} {config}';

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
    public function __construct()
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
        $configToRecord = explode(',', $this->argument('config') ?: '');
        $envToRecord = explode(',', $this->argument('env') ?: '');

        $recorder = new ContextRecordInvokable($configToRecord, $envToRecord);
        $recorder();

        // write to file
        $payload = [
            'env' => ContextRecordInvokable::getEnvironmentVariables(),
            'config' => ContextRecordInvokable::getConfigurationSettings()
        ];

        File::put(__DIR__ . '/ContextRecordCommand.record', json_encode($payload));
    }

    public static function getRecordedData(): array
    {
        $path = __DIR__ . '/ContextRecordCommand.record';
        if (File::exists($path)) {
            $content = json_decode(File::get($path), true) ?: [];
            File::delete($path);
            return $content;
        }
        return [];
    }

}
