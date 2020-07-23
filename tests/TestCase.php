<?php

namespace Cronboard\Tests;

use Cronboard\CronboardScheduleServiceProvider;
use Cronboard\CronboardServiceProvider;
use Orchestra\Database\ConsoleServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
	protected function getPackageProviders($app)
	{
	    return [
	    	CronboardServiceProvider::class,
	    	CronboardScheduleServiceProvider::class,
	    	CronboardTestsServiceProvider::class,
	    	ConsoleServiceProvider::class,
	    ];
	}
}