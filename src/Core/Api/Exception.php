<?php

namespace Cronboard\Core\Api;

use Cronboard\Core\Exceptions\Exception as CronboardException;
use Throwable;

class Exception extends CronboardException
{
    protected $code;
    protected $offline;

    public function __construct(int $code, string $message, Throwable $previous = null)
    {
        $this->code = $code;
        $this->offline = false;
        parent::__construct($message ?: "Request error ($code)", $code, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->code;
    }

    public function isOffline(): bool
    {
        return $this->offline;
    }

    public static function offline(Throwable $e = null)
    {
        return (new static(503, 'No connection to Cronboard', $e))->setOffline(true);
    }

    public static function paymentRequired(Throwable $e = null, string $errorMessage = null)
    {
        return (new static(402, $errorMessage ?: 'Payment required', $e));
    }

    protected function setOffline(bool $offline)
    {
        $this->offline = $offline;
        return $this;
    }
}
