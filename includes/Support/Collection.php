<?php
namespace App\Support;

use ArrayAccess;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use Traversable;

class Collection implements ArrayAccess, Arrayable, Countable
{
    /** @var array */
    private $items;

    public function __construct($items)
    {
        if ($items instanceof Traversable) {
            $this->items = iterator_to_array($items);
        } else {
            $this->items = (array) $items;
        }
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function map(callable $callback)
    {
        return new Collection(array_map($callback, $this->items));
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function flatMap(callable $callback)
    {
        return $this->map($callback)->collapse();
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function filter(callable $callback)
    {
        return new Collection(array_filter($this->items, $callback));
    }

    /**
     * @return Collection
     */
    public function collapse()
    {
        $results = [];

        foreach ($this->items as $values) {
            if ($values instanceof Collection) {
                $values = $values->all();
            }

            if (is_array($values)) {
                $results = array_merge($results, $values);
            }
        }

        return new Collection($results);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->items;
    }

    /**
     * @param string $glue
     * @return string
     */
    public function join($glue = "")
    {
        return implode($glue, $this->items);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->all();
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

    /**
     * @return int
     */
    public function count()
    {
        return count($this->items);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->count() === 0;
    }

    /**
     * @return bool
     */
    public function isPopulated()
    {
        return !$this->isEmpty();
    }
}
