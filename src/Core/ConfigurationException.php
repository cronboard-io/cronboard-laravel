<?php

namespace Cronboard\Core;

use Throwable;

class ConfigurationException extends Exception
{
	public static function noTokenFound(Throwable $throwable = null): ConfigurationException
	{
		return new ConfigurationException('No Cronboard.io token found. Try setting \'CRONBOARD_TOKEN\' in your .env file first.', 400, $throwable);
	}

	public static function tokenNotValid(Throwable $throwable = null): ConfigurationException
	{
		return new ConfigurationException('Your Cronboard.io token is not valid. Please verify you\'ve added the correct token in your .env file.', 400, $throwable);
	}
}