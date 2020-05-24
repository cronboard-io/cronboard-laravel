<?php

namespace Cronboard\Core\Config;

use Cronboard\Core\Api\Endpoints\Cronboard;
use Cronboard\Core\Api\Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Arr;

class ConfigurationException extends Exception {}

class Configuration
{
    protected $app;
    protected $config;
    protected $apiException;

    public function __construct(Application $app, array $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    public function check()
    {
        if (! $this->hasToken()) {
            throw new ConfigurationException(400, 'No Cronboard.io token found. Try setting \'CRONBOARD_TOKEN\' in your .env file first.');
        }

        if (! $this->isTokenValid()) {
            throw ($this->apiException ?: new ConfigurationException(400, 'Your Cronboard.io token is not valid. Please verify you\'ve added the correct token in your .env file.'));
        }
    }

    public function getBaseUrl(): string
    {
        return 'https://cronboard.io';
    }

    public function getEnabled(): bool
    {
        return $this->config['enabled'];
    }

    public function getDiscoveryPaths(): array
    {
        return Arr::get($this->config, 'discovery.paths') ?: [];
    }

    public function getDiscoveryIgnores(): array
    {
        return Arr::get($this->config, 'discovery.ignore') ?: [];
    }

    public function shouldDiscoverThirdPartyCommands(): bool
    {
        return Arr::get($this->config, 'discovery.commands.include_third_party', false);
    }

    public function getExcludedThirdPartyCommandNamespaces(): array
    {
        $excludedNamespaces = Arr::get($this->config, 'discovery.commands.exclude_namespaces');
        return is_null($excludedNamespaces) ? ['Illuminate\\'] : $excludedNamespaces;
    }

    public function getRestrictedToThirdPartyCommandNamespaces(): array
    {
        return Arr::get($this->config, 'discovery.commands.restrict_to_namespaces') ?: [];
    }

    public function shouldSilenceExternalErrors(): bool
    {
        return Arr::get($this->config, 'errors.silent', true);
    }

    public function shouldForwardExternalErrorsToHandler(): bool
    {
        return Arr::get($this->config, 'errors.report', true);
    }

    public function getDiscoveryBasePath(): string
    {
        return base_path();
    }

    public function hasToken(): bool
    {
        return !empty($this->getToken());
    }

    public function isTokenValid(): bool
    {
        return $this->hasToken() && $this->tokenIsValid($this->getToken());
    }

    public function updateToken(string $token)
    {
        $this->config['client']['token'] = $token;
    }

    public function getToken(): ?string
    {
        return $this->config['client']['token'] ?? null;
    }

    protected function tokenIsValid(string $token)
    {
        try {
            $this->apiException = null;

            $response = $this->app->make(Cronboard::class)->validateToken($token);

            return $response['valid'] ?? false;
        } catch (Exception $e) {
            $this->apiException = $e;

            if ($e->isOffline()) {
                // when offline we don't prevent the schedule from running
                return true;
            }

            return false;
        }
    }
}
