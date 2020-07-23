<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Configuration;
use Cronboard\Tests\TestCase;

class ConnectorTest extends TestCase
{
    /** @test */
    public function it_disables_cronboard_based_on_configuration()
    {
        $configArray = array_merge($this->app->config['cronboard'], [
            'enabled' => false,
            'client' => [
                'token' => 'TEST_TOKEN'
            ]
        ]);
        $configuration = new Configuration($this->app, $configArray);

        $this->app['cronboard']->loadConfiguration($configuration);

        $this->app['cronboard']->boot();

        $this->assertFalse($this->app['cronboard']->booted());
    }

    /** @test */
    public function it_disables_cronboard_when_token_missing()
    {
        $configArray = array_merge($this->app->config['cronboard'], [
            'enabled' => true,
            'client' => [
                'token' => null
            ]
        ]);
        $configuration = new Configuration($this->app, $configArray);

        $this->app['cronboard']->loadConfiguration($configuration);

        $this->app['cronboard']->boot();

        $this->assertFalse($this->app['cronboard']->booted());
    }
}