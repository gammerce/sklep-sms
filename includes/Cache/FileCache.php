<?php
namespace App\Cache;

use Exception;
use Psr\SimpleCache\CacheInterface;

// TODO Implement it
class FileCache implements CacheInterface
{
    protected $cache = [];

    public function get($key, $default = null)
    {
        if (array_key_exists($key, $this->cache)) {
            return unserialize($this->cache[$key]);
        }

        return $default;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->cache[$key] = serialize(new CacheEntity($value));
    }

    public function delete($key)
    {
        throw new Exception('Not implemented');
    }

    public function clear()
    {
        throw new Exception('Not implemented');
    }

    public function getMultiple($keys, $default = null)
    {
        throw new Exception('Not implemented');
    }

    public function setMultiple($values, $ttl = null)
    {
        throw new Exception('Not implemented');
    }

    public function deleteMultiple($keys)
    {
        throw new Exception('Not implemented');
    }

    public function has($key)
    {
        throw new Exception('Not implemented');
    }
}