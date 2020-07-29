<?php

namespace Cronboard\Console;

use Exception;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Output
{
    protected $output;

    public function __construct(OutputInterface $output = null)
    {
        $this->output = $output ?: new ConsoleOutput;

        // define styles to be used for console output
        $this->defineOutputStyles();
    }

    public function outputException(Exception $exception): Output
    {
        return $this->disabled($exception->getMessage());
    }

    public function silent(string $text): Output
    {
        return $this->line($text, 'silent');
    }

    public function disabled(string $text): Output
    {
        return $this->line($text, 'disabled');
    }

    public function error(string $text): Output
    {
        return $this->line($text, 'error');
    }

    public function comment(string $text): Output
    {
        return $this->line($text, 'comment');
    }

    /**
     * @param null|string $style
     */
    public function line(string $string, ?string $style = null): Output
    {
        $styled = $style ? "<$style>$string</$style>" : $string;
        $this->output->writeln($styled);
        return $this;
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
