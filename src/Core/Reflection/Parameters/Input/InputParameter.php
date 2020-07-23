<?php

namespace Cronboard\Core\Reflection\Parameters\Input;

use Cronboard\Core\Reflection\Parameters\Parameter;
use Cronboard\Core\Reflection\ParseParameters;

abstract class InputParameter extends Parameter
{
    protected $internalParameter;
    protected $description;
    protected $wrapperType;

    public function __construct(Parameter $internalParameter)
    {
        $this->internalParameter = $internalParameter;
        parent::__construct($internalParameter->getName(), $internalParameter->getValue());
    }

    public function getInternalParameter(): Parameter
    {
        return $this->internalParameter;
    }

    abstract public function getWrapperType(): string;

    public function getType(): string
    {
        return $this->internalParameter->getType();
    }

    public function setDescription(string $description = null): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function toArray()
    {
        return parent::toArray() + [
            'wrapperType' => $this->getWrapperType(),
            'description' => $this->description,
        ];
    }

    /**
     * Create a parameter from command input element
     * @param  \Symfony\Component\Console\Input\InputOption|\Symfony\Component\Console\Input\InputArgument $input input
     * @return Parameter
     */
    protected static function createParameterFromInput($input)
    {
        $defaultType = 'string';
        if ($default = $input->getDefault()) {
            $defaultType = static::inferPrimitiveTypeFromValue($default) ?: $defaultType;
        }
        $parameterClass = static::getPrimitiveParameterClassForType($defaultType);
        return new $parameterClass($input->getName());
    }

    protected static function parseBaseInstance(array $data): Parameter
    {
        $internalParameter = (new ParseParameters)->parseParameter($data);
        return (new static($internalParameter))
            ->setDescription($data['description'] ?? null);
    }
}
