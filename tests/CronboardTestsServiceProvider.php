<?php

namespace Cronboard\Tests;

use Cronboard\Tests\Stubs\ContextRecordCommand;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Illuminate\Support\ServiceProvider;

class CronboardTestsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ContextRecordCommand::class,
                CronboardTestCommand::class
            ]);
        }
        $this->loadMigrationsFrom(__DIR__ . '/Integration/migrations');
    }
}
