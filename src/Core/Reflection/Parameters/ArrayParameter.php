<?php

namespace Cronboard\Core\Reflection\Parameters;

use Illuminate\Contracts\Container\Container;

class ArrayParameter extends Parameter
{
    protected $name;
    protected $value;
    protected $associative;

    public function __construct(string $name, $value = null)
    {
        $this->name = $name;
        $this->value = $value;
        $this->associative = true;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getType(): string
    {
        return 'array';
    }

    public function isAssociative(): bool
    {
        return $this->associative;
    }

    public function setAssociative(bool $associative): self
    {
        $this->associative = $associative;
        return $this;
    }

    public function toArray()
    {
        return parent::toArray() + ['associative' => (int) $this->isAssociative()];
    }

    public static function parse(array $data): Parameter
    {
        return parent::parse($data)->setAssociative($data['associative'] ?? true);
    }
}
