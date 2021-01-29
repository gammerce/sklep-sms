<?php
namespace App\Cache;

use App\Exceptions\RequestException;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;

class CachingRequester
{
    const HARD_TTL = 2 * 24 * 60 * 60;

    private CacheInterface $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $cacheKey
     * @param int $ttl
     * @param callable $requestCaller
     * @return mixed
     * @throws InvalidArgumentException
     * @throws RequestException
     */
    public function load($cacheKey, $ttl, $requestCaller)
    {
        /** @var CacheEntity $entity */
        $entity = $this->cache->get($cacheKey);

        if ($entity === null) {
            return $this->fetchAndCache($cacheKey, $requestCaller);
        }

        if ($entity->olderThan($ttl)) {
            try {
                return $this->fetchAndCache($cacheKey, $requestCaller);
            } catch (RequestException $e) {
                return $entity->value;
            }
        }

        return $entity->value;
    }

    /**
     * @param string $cacheKey
     * @param callable $requestCaller
     * @return mixed
     * @throws RequestException
     * @throws InvalidArgumentException
     */
    protected function fetchAndCache($cacheKey, $requestCaller)
    {
        $response = $this->fetch($requestCaller);
        $this->cache->set($cacheKey, $response, static::HARD_TTL);
        return $response;
    }

    /**
     * @param callable $requestCaller
     * @return mixed
     * @throws RequestException
     */
    protected function fetch($requestCaller)
    {
        $response = call_user_func($requestCaller);

        if ($response === null) {
            throw new RequestException();
        }

        return $response;
    }
}
