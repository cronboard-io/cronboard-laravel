<?php

namespace Cronboard\Core;

use Throwable;

class Exception extends \RuntimeException
{
    public function __construct(string $message, $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
