<?php
namespace App\Http\Validation;

use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;

class WarningBag implements \ArrayAccess, \Countable, \IteratorAggregate, Arrayable
{
    /** @var array */
    private $warnings = [];

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

    public function getIterator()
    {
        return new ArrayIterator($this->warnings);
    }

    public function offsetExists($offset)
    {
        return isset($this->warnings[$offset]);
    }

    public function offsetGet($offset)
    {
        return array_get($this->warnings, $offset, []);
    }

    public function offsetSet($offset, $value)
    {
        $this->add($offset, $value);
    }

    public function offsetUnset($offset)
    {
        unset($this->warnings[$offset]);
    }

    public function count()
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
