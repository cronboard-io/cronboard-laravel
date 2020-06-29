<?php

namespace Cronboard\Tests;

use Cronboard\Core\Config\Configuration;
use Cronboard\Support\Storage\Storage;
use Cronboard\Tests\Stubs\ContextRecordCommand;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Support\TestStorage;
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

    public function register()
    {
        $configArray = array_merge($this->app->config['cronboard'], [
            'enabled' => true,
            'client' => [
                'token' => 'TEST_TOKEN'
            ]
        ]);
        $configuration = new Configuration($this->app, $configArray);

        $this->app['cronboard']->loadConfiguration($configuration);

        $this->app->instance(Storage::class, new TestStorage);
    }
}
