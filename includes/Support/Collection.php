<?php
namespace App\Support;

use ArrayAccess;
use ArrayIterator;
use Countable;
use Illuminate\Contracts\Support\Arrayable;
use IteratorAggregate;
use Traversable;

final class Collection implements ArrayAccess, IteratorAggregate, Arrayable, Countable
{
    use Whenable;

    /** @var array */
    private $items;

    public function __construct($items = [])
    {
        $this->items = to_array($items);
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function map(callable $callback)
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[] = call_user_func($callback, $value, $key);
        }

        return new Collection($result);
    }

    /**
     * @param callable $callback
     * @return Collection
     */
    public function mapWithKeys(callable $callback)
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = call_user_func($callback, $value, $key);
        }

        return new Collection($result);
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
        return new Collection(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
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
     * @param callable|null $callback
     * @param mixed $default
     * @return mixed
     */
    public function first(callable $callback = null, $default = null)
    {
        if ($callback === null) {
            if (empty($this->items)) {
                return $default;
            }

            foreach ($this->items as $item) {
                return $item;
            }
        }

        foreach ($this->items as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function every(callable $callback)
    {
        foreach ($this->items as $key => $value) {
            if (!call_user_func($callback, $value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param callable $callback
     * @return bool
     */
    public function some(callable $callback)
    {
        foreach ($this->items as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection
     */
    public function unique()
    {
        return new Collection(array_unique($this->items));
    }

    /**
     * @param callable|null $callback
     * @return Collection
     */
    public function sort(callable $callback = null)
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : asort($items);

        return new Collection($items);
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public function includes($item)
    {
        if (is_callable($item)) {
            foreach ($this->items as $key => $value) {
                if (call_user_func($item, $value, $key)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($item, $this->items, true);
    }

    /**
     * @param mixed $item
     * @return Collection
     */
    public function push($item)
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param array|Traversable $data
     * @return Collection
     */
    public function extend($data)
    {
        $this->items = array_merge($this->items, to_array($data));
        return $this;
    }

    /**
     * @param int $limit
     * @return Collection
     */
    public function limit($limit)
    {
        return new Collection(array_slice($this->items, 0, $limit));
    }

    /**
     * @return Collection
     */
    public function keys()
    {
        return new Collection(array_keys($this->items));
    }

    /**
     * @return Collection
     */
    public function values()
    {
        return new Collection(array_values($this->items));
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

    public function getIterator()
    {
        return new ArrayIterator($this->items);
    }
}
