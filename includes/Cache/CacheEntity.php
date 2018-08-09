<?php
namespace App\Cache;

class CacheEntity
{
    /** @var mixed */
    public $value;

    /** @var int */
    public $cachedAt;

    public function __construct($value, $cachedAt = null)
    {
        $this->value = $value;
        $this->cachedAt = $cachedAt ?: time();
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