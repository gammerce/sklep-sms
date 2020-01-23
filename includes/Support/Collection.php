<?php
namespace App\Support;

use ArrayAccess;
use Traversable;

class Collection implements ArrayAccess
{
    /** @var array */
    private $items;

    public function __construct($items)
    {
        if ($items instanceof Traversable) {
            $this->items = iterator_to_array($items);
        } else {
            $this->items = $items;
        }
    }

    public function map(callable $callback)
    {
        return new Collection(array_map($callback, $this->items));
    }

    public function filter(callable $callback)
    {
        return new Collection(array_filter($this->items, $callback));
    }

    public function toArray()
    {
        return $this->items;
    }

    public function offsetExists($offset)
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value)
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }
}
