<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Console\Output;
use Cronboard\Core\Api\Exception as CronboardApiException;
use Cronboard\Core\Configuration;
use Cronboard\Core\Context\TaskContext;
use Cronboard\Core\Exception as CronboardException;
use Cronboard\Core\Execution\Events\TaskFailed;
use Throwable;
use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Log\Events\MessageLogged;

trait Exceptions
{
    protected $consoleOutput;
    protected $offline = false;

    abstract protected function getConfiguration(): Configuration;
    abstract protected function getApplication(): Container;

    protected function bootErrorHandling()
    {
        if ($this->getApplication()->runningInConsole()) {
            $this->consoleOutput = new Output;
        }
    }

    protected function getConsoleOutput()
    {
        return optional($this->consoleOutput);
    }

    public function handleExceptionEvent(MessageLogged $event)
    {
        $exception = $this->getTaskExceptionFromMessageLogged($event);

        if ($exception) {
            $task = TaskContext::getTask();
            if ($task)     {
                $this->getApplication()->make(Dispatcher::class)->dispatch(new TaskFailed($task, $exception));
            }
        }
    }

    private function getTaskExceptionFromMessageLogged(MessageLogged $event): ?Throwable
    {
        $exception = $event->context['exception'] ?? null;
        if ($exception && $exception instanceof Throwable && ! $exception instanceof CronboardException) {
            return $exception;
        }
        return null;
    }

    public function reportException(Throwable $exception)
    {
        if ($this->consoleOutput) {
            $this->consoleOutput->outputException($exception);
        }

        $this->offline = $exception instanceof CronboardApiException && $exception->isOffline();

        if ($this->getConfiguration()->shouldSilenceExternalErrors() || $this->offline) {
            if ($this->getConfiguration()->shouldForwardExternalErrorsToHandler()) {
                $this->getApplication()->make(ExceptionHandler::class)->report($exception);
            }
        } else {
            throw $exception;
        }
    }

    protected function isOffline(): bool
    {
        return $this->offline;
    }
}
