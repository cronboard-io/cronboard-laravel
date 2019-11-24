<?php

namespace Cronboard\Core\Reflection\Parameters;

use Illuminate\Contracts\Container\Container;

class ClassParameter extends Parameter
{
	protected $name;
    protected $className;
	protected $value;

	public function __construct(string $name, string $className, $value = null)
	{
		parent::__construct($name, $value);
        $this->className = $className;
	}

	public function resolveValue(Container $container)
	{
		return $container->make($this->getClassName());
	}

	public function getType(): string
	{
		return 'class';
	}

	public function getClassName(): string
	{
		return $this->className;
	}

    protected static function parseBaseInstance(array $data): Parameter
    {
        return new static($data['name'], $data['class'], $data['value'] ?? null);
    }

    public function toArray()
    {
        return parent::toArray() + ['class' => $this->className];
    }
}
