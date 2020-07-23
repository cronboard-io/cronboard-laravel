<?php

namespace Cronboard\Tests;

use Cronboard\Core\Api\Client;
use Cronboard\Support\Storage\Storage;
use Cronboard\Tests\Stubs\ContextRecordCommand;
use Cronboard\Tests\Stubs\CronboardTestCommand;
use Cronboard\Tests\Support\TestClient;
use Cronboard\Tests\Support\TestConfiguration;
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
        $configuration = new TestConfiguration($this->app, $this->app->config['cronboard']);

        $this->app['cronboard']->loadConfiguration($configuration);

        $this->app->instance(Storage::class, new TestStorage);
        $this->app->instance(Client::class, $client = $this->app->make(TestClient::class));

        $this->app['cronboard']->setClient($client);
    }
}
