<?php

namespace Cronboard\Tests\Integration;

use Cronboard\Core\Config\Configuration;
use Cronboard\Core\Schedule as CronboardSchedule;
use Cronboard\Tests\TestCase;
use Illuminate\Console\Scheduling\Schedule;

class ConnectorTest extends TestCase
{
    /** @test */
    public function it_hooks_cronboard_into_schedule()
    {
    	$schedule = $this->app->make(Schedule::class);
        $this->assertEquals(get_class($schedule), CronboardSchedule::class);

        $configArray = array_merge($this->app->config['cronboard'], [
            'enabled' => true,
            'client' => [
                'token' => 'TEST_TOKEN'
            ]
        ]);
        $configuration = new Configuration($this->app, $configArray);

        $this->app['cronboard']->loadConfiguration($configuration);
    }

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

        $schedule = $this->app->make(Schedule::class);
        $this->assertNotEquals(get_class($schedule), CronboardSchedule::class);
        $this->assertEquals(get_class($schedule), Schedule::class);
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

        $schedule = $this->app->make(Schedule::class);
        $this->assertNotEquals(get_class($schedule), CronboardSchedule::class);
        $this->assertEquals(get_class($schedule), Schedule::class);
    }
}