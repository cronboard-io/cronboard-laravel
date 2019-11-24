<?php

namespace Cronboard\Core\Reflection;

use Cronboard\Core\Reflection\Inspector;
use Cronboard\Core\Reflection\ParameterParseException;
use Cronboard\Core\Reflection\Parameters;
use Cronboard\Core\Reflection\Parameters\ArrayParameter;
use Cronboard\Core\Reflection\Parameters\ClassParameter;
use Cronboard\Core\Reflection\Parameters\ImmutableParameter;
use Cronboard\Core\Reflection\Parameters\Input\ArgumentParameter;
use Cronboard\Core\Reflection\Parameters\Input\OptionParameter;
use Cronboard\Core\Reflection\Parameters\ModelParameter;
use Cronboard\Core\Reflection\Parameters\Parameter;
use Cronboard\Support\Action;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class ParameterParseException extends \Exception {};

class ParseParameters extends Action
{
    public function execute(array $parameters = [])
    {
        if (Arr::isAssoc($parameters)) {
            $parsedParameters = Collection::wrap($parameters)->map(function($parameters, $group) {
                return $this->parseParameterList($parameters);
            });
            return GroupedParameters::wrap($parsedParameters);
        } else {
            $parsedParameters = $this->parseParameterList($parameters);
            return Parameters::wrap($parsedParameters);
        }
    }

    protected function parseParameterList($parameters)
    {
        return Collection::wrap($parameters ?: [])->map(function($data){
            return $this->parseParameter($data);
        });
    }

    public function parseParameter(array $data)
    {
        if (isset($data['wrapperType'])) {
            $wrapperType = $data['wrapperType'];
            unset($data['wrapperType']);
            if ($wrapperType === 'argument') {
                return ArgumentParameter::parse($data);
            } else {
                return OptionParameter::parse($data);
            }
        }

        $type = $data['type'];
        switch ($type) {
            case 'model':
                return ModelParameter::parse($data);
            case 'array':
                return ArrayParameter::parse($data);
            case 'class':
                return ClassParameter::parse($data);
            case 'immutable':
                return ImmutableParameter::parse($data);
            default:
                if ($parameterClass = Parameter::getPrimitiveParameterClassForType($type)) {
                    return $parameterClass::parse($data);
                }
                throw new ParameterParseException("Could not parse parameter of type: $type");
        }
    }
}
