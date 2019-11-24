<?php

namespace Cronboard\Core\Execution\Context;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;

abstract class Override implements Arrayable
{
	protected $key;
	protected $value;
	protected $override;

	public function __construct(string $key, $override, $value = null)
	{
		$this->key = $this->normalize($key);
		$this->override = $this->valueFromArray($override);
		$this->value = $this->valueFromArray($value);
	}

    public function toArray()
    {
    	return [
    		'key' => $this->key,
			'value' => $this->valueToArray($this->value),
			'override' => $this->valueToArray($this->override),
			'type' => $this->getType(),
    	];
    }

    public function getKey(): string
    {
        return $this->key;
    }

    protected function valueFromArray($value)
    {
    	return $value;
    }

    protected function valueToArray($value)
    {
    	return $value;
    }

    public static function createFromArray(array $data)
    {
        if ($data['type'] === 'config') {
            return ConfigurationSettingOverride::fromArray($data);
        }
        return EnvironmentVariableOverride::fromArray($data);
    }

    public static function fromArray(array $data)
    {
    	return new static($data['key'], $data['override'], $data['value'] ?? null);
    }

    public function override(Container $container)
    {
    	$this->value = $this->read($container);
    	$this->write($container, $this->override);
    }

    public function restore(Container $container)
    {
    	$this->write($container, $this->value);
    }

    abstract public function read(Container $container);
    abstract public function write(Container $container, $value);

    abstract public function normalize(string $key): string;
    abstract public function getType(): string;
}
