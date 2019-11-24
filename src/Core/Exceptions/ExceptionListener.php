<?php

namespace Cronboard\Core\Exceptions;

interface ExceptionListener
{
    public function onException(Exception $exception);
}
