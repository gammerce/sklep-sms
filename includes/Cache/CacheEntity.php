<?php
namespace App\Cache;

use DateTime;

class CacheEntity
{
    /** @var mixed */
    public $value;

    /** @var DateTime */
    public $cachedAt;

    public function __construct($value, DateTime $cachedAt = null)
    {
        $this->value = $value;
        $this->cachedAt = $cachedAt ?: new DateTime();
    }

    /**
     * @param int $seconds
     * @return bool bool
     */
    public function olderThan($seconds)
    {
        return $this->cachedAt->getTimestamp() + $seconds < time();
    }
}