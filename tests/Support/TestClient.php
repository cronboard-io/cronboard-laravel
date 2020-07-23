<?php

namespace Cronboard\Tests\Support;

use Cronboard\Core\Api\Client;
use Cronboard\Core\Api\Endpoints\Account;
use Cronboard\Core\Api\Endpoints\Cronboard;
use Cronboard\Core\Api\Endpoints\Projects;
use Cronboard\Core\Api\Endpoints\Tasks;
use Illuminate\Contracts\Container\Container;
use Mockery as m;

class TestClient extends Client
{
	protected $mocked = [];

	public function mockEndpoint(Container $app, string $endpoint)
	{
		$endpointClass = get_class($this->$endpoint());
		$app->instance($endpointClass, $mock = m::mock($endpointClass));
		$this->mocked[$endpoint] = $mock;
		return $mock;
	}

	public function tasks(): Tasks
    {
        return $this->mocked['tasks'] ?? parent::tasks();
    }

    public function account(): Account
    {
        return $this->mocked['account'] ?? parent::account();
    }

    public function cronboard(): Cronboard
    {
        return $this->mocked['cronboard'] ?? parent::cronboard();
    }

    public function projects(): Projects
    {
        return $this->mocked['projects'] ?? parent::projects();
    }
}
