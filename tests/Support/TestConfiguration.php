<?php

namespace Cronboard\Tests\Support;

use Cronboard\Core\Configuration;

class TestConfiguration extends Configuration
{
	public function check()
	{

	}

	public function hasToken(): bool
	{
		return true;
	}

    public function isTokenValid(): bool
    {
    	return true;
    }
}
