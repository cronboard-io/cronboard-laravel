<?php

namespace Cronboard\Core\Execution;

use Cronboard\Core\Reflection\Parameters\ArrayParameter;
use Cronboard\Core\Reflection\Parameters\Input\ArgumentParameter;
use Cronboard\Core\Reflection\Parameters\Input\OptionParameter;
use Cronboard\Core\Reflection\Parameters\Parameter;
use Cronboard\Tasks\Task;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Collection;

abstract class ExecuteTask
{
    protected $task;
    protected $app;

    public function __construct(Task $task, Container $app)
    {
        $this->task = $task;
        $this->app = $app;
    }

    public static function create(): ExecuteTask
    {
        return new static(...func_get_args());
    }

    public function attach(Schedule $schedule)
    {
        return $schedule->call($this);
    }

    protected function extractConsoleParameterValues(Collection $parameters): array
    {
        $arguments = $parameters->filter(function($parameter) {
            return $parameter instanceof ArgumentParameter;
        });

        $options = $parameters->filter(function($parameter) {
            return $parameter instanceof OptionParameter;
        });

        $commandLineParameters = array_merge($arguments->map(function($argument) {
            return $this->formatArgumentParameter($argument);
        })->toArray(), $options->map(function($option) {
            return $this->formatOptionParameter($option);
        })->toArray());

        // remove empty arguments
        $commandLineParameters = array_filter($commandLineParameters);

        // re-index array
        $commandLineParameters = array_values($commandLineParameters);

        return $commandLineParameters;
    }

    protected function formatArgumentParameter(ArgumentParameter $parameter)
    {
        return $this->getParameterValueOrEmpty($parameter);
    }

    protected function formatOptionParameter(OptionParameter $parameter)
    {
        if ($parameter->getType() === 'boolean') {
            $parameterValue = $this->getParameterValueOrEmpty($parameter);
            if ($parameterValue === true) {
                return '--' . $parameter->getName();
            }
        } else {
            $parameterValue = $this->getParameterValueOrEmpty($parameter);
            if (!empty($parameterValue)) {
                return implode('', ['--', $parameter->getName(), '=', $parameterValue]);
            }
        }
        return null;
    }

    protected function extractParameterValues(Collection $parameters): array
    {
        return $parameters->keyBy(function($parameter) {
            return $parameter->getName();
        })->map(function($parameter) {
            return $this->getParameterValue($parameter);
        })->all();
    }

    protected function getParameterValueOrEmpty(Parameter $parameter)
    {
        return $this->getParameterValue($parameter, true);
    }

    protected function getParameterValue(Parameter $parameter, bool $allowEmpty = false)
    {
        $sources = [
            function() use ($parameter) {
                return $parameter->resolveValue($this->app);
            }
        ];

        if (!$allowEmpty) {
            $sources = array_merge($sources, [
                function() use ($parameter) {
                    return $parameter->getDefault();
                },
                function() use ($parameter) {
                    return $this->getPlaceholderValueForRequiredParameter($parameter);
                }
            ]);
        }

        foreach ($sources as $callback) {
            $value = $callback();
            if (!is_null($value)) {
                return $value;
            }
        }
        return null;
    }

    protected function getPlaceholderValueForRequiredParameter(Parameter $parameter)
    {
        if ($parameter->getRequired()) {
            if (in_array($type = $parameter->getType(), Parameter::getPrimitiveTypes())) {
                if (in_array($type, ['double', 'float', 'int'])) {
                    return 0;
                }
                if ($type === 'boolean') {
                    return false;
                }
                if ($type === 'string') {
                    return '';
                }
            } else if ($parameter instanceof ArrayParameter) {
                return [];
            }
        }
        return null;
    }

    protected function getTaskParameters()
    {
        return $this->task->getParameters();
    }
}
