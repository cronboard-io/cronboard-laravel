<?php

namespace Cronboard\Support\Signing;

use Cronboard\Core\Config\Configuration;

class Verifier
{
    protected $key;

    public function __construct(Configuration $config)
    {
        $this->key = $config->getToken();
    }

    protected function isBeingConfigured(): bool
    {
        return !empty($this->key);
    }

    public function verify(string $payloadContent, string $payloadSignature): bool
    {
        if (!$this->isBeingConfigured()) {
            return true;
        }

        $signature = hash_hmac('sha256', $payloadContent, $this->key);
        return hash_equals($payloadSignature, $signature);
    }

    public function setToken(string $token)
    {
        $this->key = $token;
    }
}
