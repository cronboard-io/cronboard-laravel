<?php

namespace Cronboard\Tests;

use Cronboard\CronboardServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
	protected function getPackageProviders($app)
	{
	    return [
	    	CronboardServiceProvider::class,
	    	CronboardTestsServiceProvider::class
	    ];
	}
}