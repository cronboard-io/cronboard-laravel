<?php

namespace Cronboard\Core\Reflection\Parameters\Input;

use Cronboard\Core\Reflection\Parameters\ArrayParameter;
use Symfony\Component\Console\Input\InputArgument;

class ArgumentParameter extends InputParameter
{
	public static function fromInputArgument(InputArgument $argument)
	{
		$internalParameter = null;

		if ($argument->isArray()) {
			$internalParameter = (new ArrayParameter($argument->getName()))->setAssociative(false);
		} else {
            $internalParameter = static::createParameterFromInput($argument);
		}

		return (new static($internalParameter))
            ->setDescription($argument->getDescription())
            ->setRequired($argument->isRequired())
            ->setDefault($argument->getDefault());
	}

    public function getWrapperType(): string
    {
        return 'argument';
    }
}
