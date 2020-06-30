<?php

namespace Cronboard\Console;

use Cronboard\Core\Exceptions\Exception;
use Cronboard\Core\Exceptions\ExceptionListener;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

class Output implements ExceptionListener
{
    protected $output;

    public function __construct(OutputInterface $output)
    {
        $this->output = $output;

        // define styles to be used for console output
        $this->defineOutputStyles();
    }

    public function onException(Exception $exception)
    {
        $this->disabled($exception->getMessage());
    }

    public function silent(string $text)
    {
        $this->line($text, 'silent');
    }

    public function disabled(string $text)
    {
        $this->line($text, 'disabled');
    }

    public function error(string $text)
    {
        $this->line($text, 'error');
    }

    public function comment(string $text)
    {
        $this->line($text, 'comment');
    }

    public function line($string, $style = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;
        $this->output->writeln($styled);
    }

    private function defineOutputStyles()
    {
        if (!$this->output->getFormatter()->hasStyle('disabled')) {
            $style = new OutputFormatterStyle('red');

            $this->output->getFormatter()->setStyle('disabled', $style);
        }

        if (!$this->output->getFormatter()->hasStyle('silent')) {
            $style = new OutputFormatterStyle('yellow');

            $this->output->getFormatter()->setStyle('silent', $style);
        }
    }
}
