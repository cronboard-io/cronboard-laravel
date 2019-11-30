<?php

namespace Cronboard\Support;

trait FrameworkInformation
{
	protected function getLaravelApplication()
	{
		return $this->app;
	}

    public function getLaravelVersionAsDouble()
    {
        $version = $this->getLaravelApplication()->version();
        if ($version) {
            $parts = explode('.', $version);
            return floatval($parts[0] . '.' . $parts[1]);
        }
        return 0;
    }

    public function getLaravelVersionAsInteger()
    {
        $version = $this->getLaravelApplication()->version();
        if ($version) {
            return intval(str_pad(str_replace('.', '', $version), 4, '0', STR_PAD_RIGHT), 10);
        }
        return 0;
    }
}
