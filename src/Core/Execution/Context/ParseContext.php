<?php

namespace Cronboard\Core\Execution\Context;

use Cronboard\Core\Execution\Context\Override;
use Cronboard\Support\Action;
use Illuminate\Support\Collection;

class ContextParseException extends \Exception {};

class ParseContext extends Action
{
    public function execute(array $context = null)
    {
        return Collection::wrap($context ?: [])->map(function($data) {
            return $this->parseContextOverride($data);
        })->sort(function($override) {
            return $override->getType() == 'env' ? 1 : 2;
        })->values();
    }

    public function parseContextOverride(array $data)
    {
        return Override::createFromArray($data);
    }
}
