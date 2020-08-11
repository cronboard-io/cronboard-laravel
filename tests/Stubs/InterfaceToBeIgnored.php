<?php

namespace Cronboard\Tests\Stubs;

use Illuminate\Contracts\Queue\ShouldQueue;

// should be ignored during discovery
interface InterfaceToBeIgnored extends ShouldQueue
{
	
}
