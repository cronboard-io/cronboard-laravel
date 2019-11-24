<?php

namespace Cronboard\Tests\Stubs;

use Cronboard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CronboardTestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $model;
    protected $options;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(CronboardModel $model, array $options = [])
    {
        $this->model = $model;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        Cronboard::report('CronboardTestJob', 5);
    }

    public function getJobOptions(): array
    {
        return $this->options;
    }

    public function getJobModel(): ?CronboardModel
    {
        return $this->model;
    }
}
