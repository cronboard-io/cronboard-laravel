<?php

namespace Cronboard\Console;

use Closure;
use Cronboard\Core\Api\Endpoints\Cronboard;
use Cronboard\Support\Environment;
use Dotenv\Dotenv;
use Illuminate\Console\Command;

class InstallCommand extends Command
{
    const CRONBOARD_TOKEN_KEY = 'CRONBOARD_TOKEN';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cronboard:install {token?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Cronboard and setup your project';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tokenFromConfig = $this->laravel['config']->get('cronboard.client.token');
        $tokenAsArgument = $this->argument('token');

        $token = $tokenAsArgument ?: $tokenFromConfig;

        if (empty($token)) {
            $this->error('No token was found. Please provide a Cronboard token to this command.');
            return 1;
        }

        $environmentData = (new Environment($this->laravel))->toArray();
        $response = $this->laravel->make(Cronboard::class)->install($token, $environmentData);

        if ($response['success'] ?? false) {
            $tokenArgumentIsDifferentFromConfig = $tokenAsArgument && (! $tokenFromConfig || $tokenFromConfig !== $tokenAsArgument);
            $tokenExistsInConfigButNotInEnvFile = $tokenFromConfig && ! $this->environmentVariableExists();
            $tokenIsBeingUpdated = $tokenArgumentIsDifferentFromConfig || $tokenExistsInConfigButNotInEnvFile;

            if ($tokenIsBeingUpdated) {
                $this->setTokenInEnvironmentFile($token);
            }

            $this->updateTokenInRuntime($token);
            $shouldAskToRefreshCache = false;

            if ($tokenIsBeingUpdated) {
                $shouldAskToRefreshCache = $this->laravel->configurationIsCached();
            }

            $this->info($response['message'] ?: 'Installation completed.');

            if (! $shouldAskToRefreshCache) {
                $this->call('cronboard:status');
            } else {
                $this->warn('Your configuration is cached - please run `php artisan config:cache` to make sure Cronboard\'s configuration is included.');
            }

            return 0;
        } else {
            $this->error($response['message']);
            return 1;
        }
    }

    private function updateTokenInRuntime($token)
    {
        $this->laravel['config']->set('cronboard.client.token', $token);
        $this->laravel['cronboard']->updateToken($token);
    }

    private function environmentVariableExists(): bool
    {
        $dotenv = null;
        if (method_exists(Dotenv::class, 'create')) {
            $dotenv = Dotenv::create($this->laravel->basePath());    
        } else {
            $dotenv = new Dotenv($this->laravel->basePath());
        }
        
        $variables = $dotenv->safeLoad();
        $value = $variables[static::CRONBOARD_TOKEN_KEY] ?? null;
        
        return ! empty($value);
    }

    private function setTokenInEnvironmentFile($token)
    {
        if ($this->environmentVariableExists()) {
            $this->replaceTokenInEnvFile($token);
        } else {
            $this->insertTokenInEnvFile($token);
        }

        $this->line('Cronboard token written in .env file.');
    }

    private function replaceTokenInEnvFile($token)
    {
        $this->modifyEnvFileContents(function($contents) use ($token) {
            return preg_replace('/'.static::CRONBOARD_TOKEN_KEY.'=.*/', $this->getTokenLine($token), $contents);
        });
    }

    private function insertTokenInEnvFile($token)
    {
        $this->modifyEnvFileContents(function($contents) use ($token) {
            return $contents . PHP_EOL . $this->getTokenLine($token);
        });
    }

    private function modifyEnvFileContents(Closure $modifier)
    {
        $envFileContents = file_get_contents($this->laravel->environmentFilePath());
        $envFileContents = $modifier($envFileContents);
        file_put_contents($this->laravel->environmentFilePath(), $envFileContents);
    }

    private function getTokenLine($token)
    {
        return static::CRONBOARD_TOKEN_KEY . "=$token";
    }
}
