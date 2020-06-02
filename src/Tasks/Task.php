<?php

namespace Cronboard\Tasks;

use Cronboard\Commands\Command;
use Cronboard\Core\Reflection\Parameters;

class Task
{
    protected $key;
    protected $originalTaskKey;
    protected $command;
    protected $parameters;
    protected $constraints;
    protected $custom;
    protected $runtime;
    protected $single;
    protected $details;
    protected $failed;

    public function __construct(string $key, Command $command, Parameters $parameters, array $constraints, bool $custom = false)
    {
        $this->key = $key;
        $this->command = $command;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
        $this->custom = $custom;
        $this->runtime = false;
        $this->single = false;
        $this->failed = false;
        $this->details = [];
    }

    public function getCommand(): Command
    {
        return $this->command;
    }

    public function setCommand(Command $command)
    {
        $this->command = $command;
    }

    public function getDetail(string $key)
    {
        return $this->details[$key] ?? null;
    }

    public function setDetails(array $details)
    {
        $this->details = $details;
    }

    public function setCronboardTask(bool $custom)
    {
        $this->custom = $custom;
    }

    public function isCronboardTask(): bool
    {
        return $this->custom;
    }

    public function isApplicationTask(): bool
    {
        return !$this->isCronboardTask();
    }

    public function isSingleExecution(): bool
    {
        return $this->single;
    }

    public function isRuntimeTask(): bool
    {
        return $this->runtime;
    }

    public function setFailed()
    {
        $this->failed = true;
    }

    public function hasFailed(): bool
    {
        return $this->failed;
    }

    public function setSingleExecution(bool $single)
    {
        $this->single = $single;
    }

    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'command' => $this->command->getKey(),
            'parameters' => $this->parameters->toArray(),
            'constraints' => $this->constraints,
        ];
    }

    public function getParameters(): Parameters
    {
        return $this->parameters;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getOriginalTaskKey(): ?string
    {
        return $this->originalTaskKey;
    }

    public function aliasAsRuntimeInstance(string $key, bool $single = false)
    {
        return $this->aliasAsTaskInstance($key, [
            'custom' => true,
            'runtime' => true,
            'single' => $single
        ]);
    }

    private function aliasAsTaskInstance(string $key, array $attributes = [])
    {
        return tap(clone $this, function($instance) use ($key, $attributes) {
            $instance->key = $key;
            $instance->originalTaskKey = $this->getKey();
            foreach ($attributes as $attribute => $value) {
                $instance->$attribute = $value;
            }
            return $instance;
        });
    }
}
