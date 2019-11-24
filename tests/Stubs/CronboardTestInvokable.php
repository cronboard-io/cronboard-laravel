<?php

namespace Cronboard\Tests\Stubs;

use Cronboard\Commands\CommandMetadataProvider;
use Cronboard;

class CronboardTestInvokable implements CommandMetadataProvider
{
    protected $parameter;

    public function __construct(int $invokableConstructorParameter = 5)
    {
        $this->parameter = $invokableConstructorParameter;
    }

    public function getConstructorParameter(): int
    {
        return $this->parameter;
    }

    public function __invoke(string $third = '2 boxes of lemons')
    {
        Cronboard::report('Flowers', 'x2');
        Cronboard::report('Apples', 'x3');
        Cronboard::report('Extra Order', $third);
    }

    public function getCommandName(): string
    {
        return 'Cronboard Test Invokable';
    }

    public function getCommandDescription(): string
    {
        return 'Cronboard Task from an Invokable';
    }
}
