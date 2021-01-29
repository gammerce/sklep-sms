<?php
namespace App\Cache;

class CacheEntity
{
    /** @var mixed */
    public $value;
    public int $cachedAt;

    public function __construct($value, ?int $cachedAt = null)
    {
        $this->value = $value;
        $this->cachedAt = $cachedAt ?? time();
    }

    /**
     * @param int $seconds
     * @return bool bool
     */
    public function olderThan($seconds)
    {
        return $this->cachedAt + $seconds < time();
    }
}
