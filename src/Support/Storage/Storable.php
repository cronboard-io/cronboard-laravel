<?php

namespace Cronboard\Support\Storage;

use Illuminate\Container\Container;

trait Storable
{
	public static function getStorage(): Storage
	{
		static $storage = null;

		if (is_null($storage)) {
			$storage = Container::getInstance()->make(Storage::class);
		}

		return $storage;
	}

	protected function load(string $key = null)
	{
		$data = static::getStorage()->get($key ?: $this->getStorableKey());
        $data = (array) $data ?: [];
		$this->loadFromArray($data);
	}

	protected function store()
	{
		static::getStorage()->store($this->getStorableKey(), $this->toArray());
	}

	protected function destroy()
    {
        static::getStorage()->remove($this->getStorableKey());
    }

	abstract public function toArray(): array;

	abstract public function loadFromArray(array $array);

	abstract public function getStorableKey(): string;
}
