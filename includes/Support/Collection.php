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

    private array $items;

    public function __construct($items = [])
    {
        $this->items = to_array($items);
    }

    public function map(callable $callback): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[] = call_user_func($callback, $value, $key);
        }

        return new Collection($result);
    }

    public function mapWithKeys(callable $callback): self
    {
        $result = [];

        foreach ($this->items as $key => $value) {
            $result[$key] = call_user_func($callback, $value, $key);
        }

        return new Collection($result);
    }

    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->collapse();
    }

    public function filter(callable $callback): self
    {
        return new Collection(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    public function collapse(): self
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
    public function first(callable $callback = null, $default = null): mixed
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

    public function every(callable $callback): bool
    {
        foreach ($this->items as $key => $value) {
            if (!call_user_func($callback, $value, $key)) {
                return false;
            }
        }

        return true;
    }

    public function some(callable $callback): bool
    {
        foreach ($this->items as $key => $value) {
            if (call_user_func($callback, $value, $key)) {
                return true;
            }
        }

        return false;
    }

    public function unique(): self
    {
        return new Collection(array_unique($this->items));
    }

    public function sort(?callable $callback = null): self
    {
        $items = $this->items;

        $callback ? uasort($items, $callback) : asort($items);

        return new Collection($items);
    }

    /**
     * @param mixed $item
     * @return bool
     */
    public function includes($item): bool
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
     * @return self
     */
    public function push($item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * @param array|Traversable $data
     * @return self
     */
    public function extend($data): self
    {
        $this->items = array_merge($this->items, to_array($data));
        return $this;
    }

    /**
     * @param int $limit
     * @return self
     */
    public function limit($limit): self
    {
        return new Collection(array_slice($this->items, 0, $limit));
    }

    public function keys(): self
    {
        return new Collection(array_keys($this->items));
    }

    public function values(): self
    {
        return new Collection(array_values($this->items));
    }

    public function all(): array
    {
        return $this->items;
    }

    /**
     * @param string $glue
     * @return string
     */
    public function join($glue = ""): string
    {
        return implode($glue, $this->items);
    }

    public function toArray(): array
    {
        return $this->all();
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->items[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }

    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    public function isPopulated(): bool
    {
        return !$this->isEmpty();
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }
}
