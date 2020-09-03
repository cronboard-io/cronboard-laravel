<?php

namespace Cronboard\Support;

use Cronboard\Core\Cronboard;
use Cronboard\Support\CommandContext;
use Cronboard\Support\Composer;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;

class Environment implements Arrayable
{
    protected $app;

    public function __construct(Container $app)
    {
        $this->app = $app;
    }

    public function toArray()
    {
        static $data = null;

        if (is_null($data)) {
            $data = $this->getData();
        }

        return $data;
    }

    private function getData(): array
    {
        return array_merge(
            $this->getProjectInformation(),
            $this->getLaravelInformation(),
            $this->getClientInformation(),
            $this->getEnvironmentInformation()
        );
    }

    private function getProjectInformation(): array
    {
        return [
            'project.name' => $this->app['config']['app']['name'] ?? null,
            'timezone' => $this->app['config']['app']['timezone'] ?? 'UTC',
            'environment' => $this->app['config']['app']['env'] ?? 'production',
            'framework' => 'Laravel',
        ];
    }

    private function getLaravelInformation(): array
    {
        return [
            'laravel.version' => $this->app->version(),
        ];
    }

    private function getClientInformation(): array
    {
        $composerContents = Composer::getComposerContents(base_path('composer.json'));
        $package = 'cronboard-io/cronboard-laravel';
        $requiredVersion = $composerContents['require'][$package] ?? null;
        $requiredVersionDev = $composerContents['require-dev'][$package] ?? null;
        return [
            'client.version' => $requiredVersion ?: ($requiredVersionDev ?: null),
            'cronboard.version' => defined(Cronboard::class . '::VERSION') ? Cronboard::VERSION : null,
        ];
    }

    private function getEnvironmentInformation(): array
    {
        $commandContext = new CommandContext($this->app);

        return [
            'php.version' => phpversion(),
            'cli.command' => $commandContext->getConsoleCommandName()
        ];
    }
}
