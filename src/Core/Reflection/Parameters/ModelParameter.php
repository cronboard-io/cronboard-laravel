<?php

namespace Cronboard\Core\Reflection\Parameters;

use Illuminate\Contracts\Container\Container;

class ModelParameter extends ClassParameter
{
	public function getType(): string
	{
		return 'model';
	}

	public function resolveValue(Container $container)
	{
		$modelClass = $this->getClassName();
        $value = null;

        $modelId = $this->getValue();
        if (empty($modelId)) {
            return new $modelClass;
        }

        if ($this->getRequired()) {
            $value = $modelClass::findOrFail($modelId);
        } else {
            $value = $modelClass::find($modelId);
        }

        return $value;
	}
}
