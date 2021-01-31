<?php
namespace App\Support;

trait Whenable
{
    /**
     * @param bool $condition
     * @param callable $callback
     * @return self
     */
    public function when($condition, callable $callback): self
    {
        if ($condition) {
            call_user_func($callback, $this);
        }

        return $this;
    }
}
