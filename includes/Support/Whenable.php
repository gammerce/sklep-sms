<?php
namespace App\Support;

trait Whenable
{
    /**
     * @param bool $condition
     * @param callable $callback
     * @return $this
     */
    public function when($condition, callable $callback)
    {
        if ($condition) {
            call_user_func($callback, $this);
        }

        return $this;
    }
}
