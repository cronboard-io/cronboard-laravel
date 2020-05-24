<?php

namespace Cronboard\Tasks;

use Cronboard\Commands\Command;
use Cronboard\Core\Reflection\Parameters;

class Task
{
    protected $key;
    protected $command;
    protected $parameters;
    protected $constraints;
    protected $custom;
    protected $singleExecution;
    protected $details;

    public function __construct(string $key, Command $command, Parameters $parameters, array $constraints, bool $custom = false)
    {
        $this->key = $key;
        $this->command = $command;
        $this->parameters = $parameters;
        $this->constraints = $constraints;
        $this->custom = $custom;
        $this->singleExecution = false;
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

    public function setSingleExecution(bool $singleExecution)
    {
        $this->singleExecution = $singleExecution;
    }

    public function isApplicationTask(): bool
    {
        return !$this->isCronboardTask();
    }

    public function isSingleExecution(): bool
    {
        return !!$this->singleExecution;
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

    public function aliasAsCustomTask(string $key)
    {
        return $this->aliasAsTaskInstance($key, [
            'custom' => true
        ]);
    }

    public function aliasAsTaskInstance(string $key, array $attributes = [])
    {
        return tap(clone $this, function($instance) use ($key, $attributes) {
            $instance->key = $key;
            foreach ($attributes as $attribute => $value) {
                $instance->$attribute = $value;
            }
            return $instance;
        });
    }
}
