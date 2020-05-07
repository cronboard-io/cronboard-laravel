<?php

namespace Cronboard\Core\Reflection;

use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\ArrayParameter;
use Cronboard\Core\Reflection\Parameters\ClassParameter;
use Cronboard\Core\Reflection\Parameters\ImmutableParameter;
use Cronboard\Core\Reflection\Parameters\ModelParameter;
use Cronboard\Core\Reflection\Parameters\Parameter;
use Cronboard\Core\Reflection\Report;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use ReflectionClass;
use ReflectionMethod;

class Inspector
{
    protected $class;

	public function __construct(string $class = null)
    {
        $this->class = $class;
    }

    public function getReport(): Report
    {
        if (empty($this->class) || ! class_exists($this->class)) {
            return new Report(false);
        }

        $reflectionClass = new ReflectionClass($this->class);
        if (! $reflectionClass->isInstantiable()) {
            return new Report(false);
        }

        $constructor = $reflectionClass->getConstructor();
        $parameters = new Parameters;
        if (! empty($constructor)) {
            $parameters = Parameters::wrap($this->inspectMethodParameters($constructor));
        }

        return new Report(true, $parameters);
    }

    public function getSupportedPrimitiveTypes(): array
    {
        return ['int', 'float', 'double', 'string', 'boolean'];
    }

    public function inspectMethodParameters(ReflectionMethod $method): Collection
    {
        return Collection::wrap($method->getParameters())->map(function($reflectionParameter){
            $type = $reflectionParameter->getType();
            $value = $reflectionParameter->isDefaultValueAvailable() ? $reflectionParameter->getDefaultValue() : null;
            $name = $reflectionParameter->getName();

            if (! empty($type)) {
                $type = $type->getName();

                $parameter = null;

                if (in_array($type, Parameter::getPrimitiveTypes())) {
                    $parameterClass = Parameter::getPrimitiveParameterClassForType($type);
                    $parameter = (new $parameterClass($name))->setDefault($value);
                }

                if ($type === 'array') {
                    $parameter = (new ArrayParameter($name))->setDefault($value);
                }
                
                if (class_exists($type)) {
                    if (is_subclass_of($type, Model::class)) {
                        $parameter = new ModelParameter($name, $type);
                    } else {
                        $parameter = new ClassParameter($name, $type);
                    }
                }

                if (! empty($parameter)) {
                    return $parameter->setRequired(! $reflectionParameter->isDefaultValueAvailable());
                }
            }

            return (new ImmutableParameter($name))
                ->setDefault($value)
                ->setRequired(! $reflectionParameter->isDefaultValueAvailable());
        });
    }
}
