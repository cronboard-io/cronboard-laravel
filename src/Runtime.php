<?php

namespace Cronboard;

use BadMethodCallException;
use Cronboard\Core\Cronboard;

class Runtime
{
    protected $cronboard;

    public function __construct(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;
    }

    public function setCronboard(Cronboard $cronboard)
    {
        $this->cronboard = $cronboard;
    }

    public function __call($method, $arguments)
    {
        if (in_array($method, $this->getContextMethods())) {
            if ($context = $this->cronboard->getContext()) {
                return call_user_func_array([$context, $method], $arguments);
            }
            return;
        }

        if (in_array($method, $this->getRuntimeMethods())) {
            return call_user_func_array([$this->cronboard, $method], $arguments);
        }

        throw new BadMethodCallException("Method [$method] does not exist");
    }

    private function getContextMethods(): array
    {
        return [
            'report'
        ];
    }

    private function getRuntimeMethods(): array
    {
        return [
            'extend',
            'dontTrack',
        ];
    }
}
