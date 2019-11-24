<?php

namespace Cronboard\Commands;

use Cronboard\Commands\CommandByAlias;
use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\Inspectors\ConsoleCommandInspector;
use Cronboard\Core\Reflection\Inspectors\InvokableCommandInspector;
use Cronboard\Core\Reflection\Inspectors\JobCommandInspector;
use Cronboard\Core\Reflection\Parameters;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class Command
{
    protected $type;
    protected $handler;
    protected $alias;
    protected $flags;

    public function __construct($type, $handler, $alias = null)
    {
        $this->type = $type;
        $this->handler = $handler;
        $this->alias = $alias;
        $this->flags = [];
    }

    public function set(string $flag, bool $set = true)
    {
        if ($set) {
            if (! in_array($flag, $this->flags)) {
                $this->flags[] = $flag;
            }
        } else {
            $this->flags = array_filter($this->flags, function($arrayFlag) use ($flag) {
                return $arrayFlag !== $flag;
            });
        }
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getHandler(): string
    {
        return $this->handler;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function byAlias(string $string): CommandByAlias
    {
        return new CommandByAlias($this->type, $string);
    }

    public static function exec(string $handler): Command
    {
        return new static('exec', $handler);
    }

    public static function command(string $handler = null, string $alias = null): Command
    {
        return new static('command', $handler, $alias);
    }

    public static function invokable(string $handler): Command
    {
        return new static('invokable', $handler);
    }

    public static function job(string $handler): Command
    {
        return new static('job', $handler);
    }

    public static function closure(): Command
    {
        return new static('closure', 'Closure');
    }

    public function isConsoleCommand(): bool
    {
        return $this->type === 'command';
    }

    public function isAliasedConsoleCommand(): bool
    {
        return $this->isConsoleCommand() && $this instanceof CommandByAlias;
    }

    public function isClosureCommand(): bool
    {
        return $this->type === 'closure';
    }

    public function isExecCommand(): bool
    {
        return $this->type === 'exec';
    }

    public function isInvokableCommand(): bool
    {
        return $this->type === 'invokable';
    }

    public function isJobCommand(): bool
    {
        return $this->type === 'job';
    }

    public function resolveHandlerByContainer(Container $container)
    {
        // try to resolve console commands from console kernel
        if ($this->isConsoleCommand()) {
            $consoleKernel = $container->make(Kernel::class);
            $instance = Arr::get($consoleKernel->all(), $this->getAlias());
            if ($instance) {
                return $instance;
            }
        }
        return $container->make($this->handler);
    }

    public function getKey()
    {
        return md5(implode('-', [$this->type, $this->handler]));
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'handler' => $this->handler,
            'alias' => $this->alias,
            'key' => $this->getKey(),
            'parameters' => $this->getParameters()->toArray(),
            'flags' => $this->flags
        ];
    }

    public function getInspector(): Inspector
    {
        $inspector = new Inspector($this->handler);
        if ($this->isConsoleCommand()) {
            return new ConsoleCommandInspector($this->handler);
        }
        if ($this->isJobCommand()) {
            return new JobCommandInspector($this->handler);
        }
        if ($this->isInvokableCommand()) {
            return new InvokableCommandInspector($this->handler);
        }
        return $inspector;
    }

    public function getParameters(): Parameters
    {
        return $this->getInspector()->getReport()->getParameters();
    }
}
