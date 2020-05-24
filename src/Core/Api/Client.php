<?php

namespace Cronboard\Core\Api;

use Cronboard\Core\Config\Configuration;
use GuzzleHttp\Client as GuzzleClient;

class Client extends GuzzleClient
{
    protected $token;

    public function __construct(Configuration $config, array $options = [])
    {
        $options['base_uri'] = $options['base_uri'] ?? $config->getBaseUrl();

        parent::__construct($options);

        $this->token = $config->getToken();
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
}
