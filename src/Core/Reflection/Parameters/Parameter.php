<?php

namespace Cronboard\Core\Reflection\Parameters;

use Cronboard\Core\Reflection\Parameters\Primitive\BooleanParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\DoubleParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\FloatParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\IntegerParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\StringParameter;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Support\Arrayable;

abstract class Parameter implements Arrayable
{
	protected $name;
	protected $value;
	protected $default;
	protected $required;

	public function __construct(string $name, $value = null)
	{
		$this->name = $name;
		$this->value = $value;

		$this->default = null;
		$this->required = true;
	}

	public static function create(): Parameter
	{
		return new static(...func_get_args());
	}

    public static function parse(array $data): Parameter
    {
        return static::parseBaseInstance($data)
            ->setRequired($data['required'] ?? true)
            ->setDefault($data['default'] ?? null);
    }

    protected static function parseBaseInstance(array $data): Parameter
    {
        return (new static($data['name'], $data['value'] ?? null));
    }

	public function isMutable(): bool
	{
		return true;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function setValue($value): Parameter
	{
		$this->value = $value;
		return $this;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function resolveValue(Container $container)
	{
		return $this->getValue();
	}

    public function toArray()
    {
        $array = [
            'name' => $this->getName(),
            'value' => $this->getValue(),
            'type' => $this->getType(),
            'required' => $this->getRequired(),
            'default' => $this->getDefault(),
        ];
        $array['id'] = md5(json_encode($array));
        return $array;
    }

	abstract public function getType(): string;

	public function setRequired(bool $required): Parameter
	{
		$this->required = $required;
		return $this;
	}

	public function getRequired(): bool
	{
		return !! $this->required;
	}

	public function setDefault($default): Parameter
	{
		$this->default = $default;
		return $this;
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function hasDefault(): bool
	{
		return ! is_null($this->getDefault());
	}

	public function isDefaultValue($value): bool
	{
		return $this->hasDefault() && $value === $this->getDefault();
	}

    public static function inferPrimitiveTypeFromValue($value)
    {
        if (is_null($value)) return null;
        if (is_bool($value)) return 'boolean';
        if (is_double($value)) return 'double';
        if (is_float($value)) return 'float';
        if (is_numeric($value)) return 'int';
        if (is_string($value)) return 'string';
        return null;
    }

    public static function getPrimitiveTypes(): array
    {
    	return ['boolean', 'double', 'float', 'int', 'string'];
    }

    public static function getPrimitiveParameterClassForType(string $type)
    {
    	if (in_array($type, static::getPrimitiveTypes())) {
    		if ($type === 'boolean') {
    			return BooleanParameter::class;
    		}
    		if ($type === 'double') {
    			return DoubleParameter::class;
    		}
    		if ($type === 'float') {
    			return FloatParameter::class;
    		}
    		if ($type === 'int') {
    			return IntegerParameter::class;
    		}
    		if ($type === 'string') {
    			return StringParameter::class;
    		}
    	}
    	return null;
    }
}
