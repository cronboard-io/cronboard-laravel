<?php

namespace Cronboard\Console\Concerns;

use Cronboard\Core\Cronboard;

trait HasAccessToCronboard
{
    protected function getCronboard(): Cronboard
    {
        return $this->laravel['cronboard'];
    }

    protected function isCronboardActive(): bool
    {
        return $this->getCronboard()->booted();
    }
}
