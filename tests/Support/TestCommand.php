<?php

namespace Cronboard\Tests\Support;

use Cronboard\Commands\Command;

class TestCommand extends Command
{
	public function exists(): bool
	{
		return true;
	}
}
