<?php

namespace Cronboard\Tasks\Context;

use Exception;

class ExceptionModule extends Module
{
	protected $exception;

	public function load(array $data)
	{
		$this->exception = $data['exception'] ?? null;
	}

	public function toArray(): array
	{
		return [
			'exception' => $this->exception
		];
	}

	public function getHooks(): array
	{
		return [
			'getException',
			'setException'
		];
	}

	public function shouldStoreAfter(string $hookName): bool
	{
		return $hookName === 'setException';
	}

	public function setException(Exception $exception)
    {
        $this->exception = $this->exceptionToArray($exception);
    }

    private function exceptionToArray(Exception $exception = null)
    {
        return [
            'class' => get_class($exception),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'message' => $exception->getMessage(),
        ];
    }

    public function getException()
    {
        return $this->exception;
    }
}
