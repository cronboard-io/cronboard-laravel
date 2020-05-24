<?php

namespace Cronboard\Core\Concerns;

use Cronboard\Console\Output as CronboardConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

trait Output
{
    protected $output;

    public function registerOutputStream(OutputInterface $output)
    {
        $this->output = new CronboardConsoleOutput($output);

        // register output to write errors to the console
        $this->registerExceptionListener($this->output);
    }

    public function getOutput()
    {
        return $this->output;
    }
}
