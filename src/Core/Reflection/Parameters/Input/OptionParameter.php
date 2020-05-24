<?php

namespace Cronboard\Core\Reflection\Parameters\Input;

use Cronboard\Core\Reflection\Parameters\ArrayParameter;
use Cronboard\Core\Reflection\Parameters\Primitive\BooleanParameter;
use Symfony\Component\Console\Input\InputOption;

class OptionParameter extends InputParameter
{
    public static function fromInputOption(InputOption $option)
    {
        $internalParameter = null;

        if ($option->isArray()) {
            $internalParameter = (new ArrayParameter($option->getName()))->setAssociative(false);
        } else if (! $option->acceptValue()) {
            $internalParameter = new BooleanParameter($option->getName());
        } else {
            $internalParameter = static::createParameterFromInput($option);
        }

        return (new static($internalParameter))
            ->setDescription($option->getDescription())
            ->setRequired($option->isValueRequired())
            ->setDefault($option->getDefault());
    }

    public function getWrapperType(): string
    {
        return 'option';
    }
}
