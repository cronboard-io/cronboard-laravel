<?php

namespace Cronboard\Console\Concerns;

use Cronboard\Core\Config\Configuration;

trait ValidatesCronboardConfiguration
{
    protected function validateCronboardConfiguration(): bool
    {
    	$configuration = $this->laravel->make(Configuration::class);
        if (! $configuration->hasToken()) {
            $this->error('No Cronboard.io token found. Try running \'cronboard:install\' first.');
            return false;
        } else if (! $configuration->isTokenValid()) {
            $this->error('Your Cronboard.io token is not valid. Try clearing it and running \'cronboard:install\'.');
            return false;
        } else {
            return true;
        }
    }
}
