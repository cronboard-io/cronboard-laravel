<?php

namespace Cronboard\Core\Api;

use Cronboard\Core\Api\Endpoints\Account;
use Cronboard\Core\Api\Endpoints\Cronboard;
use Cronboard\Core\Api\Endpoints\Projects;
use Cronboard\Core\Api\Endpoints\Tasks;
use Cronboard\Core\Configuration;
use Cronboard\Support\Signing\Verifier;
use GuzzleHttp\Client as GuzzleClient;

class Client extends GuzzleClient
{
    protected $token;
    protected $requestVerifier;

    public function __construct(Configuration $config, Verifier $requestVerifier, array $options = [])
    {
        parent::__construct(array_merge([
            'base_uri' => $config->getBaseUrl()
        ], $options));

        $this->token = $config->getToken();
        $this->requestVerifier = $requestVerifier;
    }

    public function getAuthHeaders(): array
    {
        if (empty($this->token)) {
            return [];
        }
        return [
            'Authorization' => 'Bearer ' . $this->token
        ];
    }

    public function setToken(string $token)
    {
        $this->token = $token;
    }

    public function tasks(): Tasks
    {
        return new Tasks($this, $this->requestVerifier);
    }

    public function account(): Account
    {
        return new Account($this, $this->requestVerifier);
    }

    public function cronboard(): Cronboard
    {
        return new Cronboard($this, $this->requestVerifier);
    }

    public function projects(): Projects
    {
        return new Projects($this, $this->requestVerifier);
    }
}
