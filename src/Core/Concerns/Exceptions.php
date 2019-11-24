<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Core\Api\Exception as CronboardApiException;
use Cronboard\Core\Exceptions\Exception as CronboardException;
use Cronboard\Core\Exceptions\ExceptionListener;
use Cronboard\Core\Execution\Events\TaskFailed;
use Exception;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;

trait Exceptions
{
    protected $exceptionListeners;

    public function reportException(CronboardException $exception)
    {
        $this->forwardExceptionToListeners($exception);

        $isCronboardOffline = $exception instanceof CronboardApiException && $exception->isOffline();
        if ($isCronboardOffline) {
            $this->setOfflineDueTo($exception);
        }

        if ($this->config->shouldSilenceExternalErrors() || $isCronboardOffline) {
            if ($this->config->shouldForwardExternalErrorsToHandler()) {
                $this->app->make(ExceptionHandler::class)->report($exception);
            }
        } else {
            throw $exception;
        }
    }

    protected function forwardExceptionToListeners(CronboardException $exception)
    {
        $this->exceptionListeners->each->onException($exception);
    }

    public function registerExceptionListener(ExceptionListener $listener)
    {
        $this->exceptionListeners[] = $listener;
    }

    public function handleExceptionEvent(MessageLogged $event)
    {
        if ($this->shouldRecordExceptionEvent($event)) {
            $exception = $event->context['exception'];
            $this->failWithException($exception);
        }
    }

    public function failWithException(Exception $exception)
    {
        if ($this->insideTaskContext()) {
            $taskContext = $this->getContext();
            $task = $this->getTaskByKey($taskContext->getTask());

            $taskContext->setException($exception);

            $dispatcher = $this->app->make(Dispatcher::class);
            $dispatcher->dispatch(new TaskFailed($task));
        }
    }

    private function shouldRecordExceptionEvent(MessageLogged $event)
    {
        return $this->insideTaskContext() && $this->eventContainsValidException($event);
    }

    private function insideTaskContext()
    {
        if ($taskContext = $this->getContext()) {
            return ! empty($taskContext->getTask());
        }
        return false;
    }

    private function eventContainsValidException($event)
    {
        return isset($event->context['exception']) && $event->context['exception'] instanceof Exception && ! ($event->context['exception'] instanceof CronboardException);
    }
}
