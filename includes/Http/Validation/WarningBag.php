<?php
namespace App\Http\Validation;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use Traversable;

class WarningBag implements ArrayAccess, Countable, IteratorAggregate, Arrayable
{
    private array $warnings = [];

    public function add($key, $warning)
    {
        if (!array_key_exists($key, $this->warnings)) {
            $this->warnings[$key] = [];
        }

        if (!is_iterable($warning)) {
            $warning = [$warning];
        }

        foreach ($warning as $message) {
            $this->warnings[$key][] = $message;
        }
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->warnings;
    }

    public function toArray()
    {
        return $this->all();
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->warnings);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->warnings[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return array_get($this->warnings, $offset, []);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->add($offset, $value);
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->warnings[$offset]);
    }

    public function count(): int
    {
        return count($this->warnings);
    }

    public function isEmpty()
    {
        return $this->count() === 0;
    }

    public function isPopulated()
    {
        return !$this->isEmpty();
    }
}
