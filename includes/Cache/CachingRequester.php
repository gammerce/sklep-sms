<?php
namespace App\Cache;

use App\Exceptions\LicenseRequestException;
use Closure;
use Psr\SimpleCache\CacheInterface;

class CachingRequester
{
    const HARD_TTL = 2 * 24 * 60 * 60;

    /** @var CacheInterface */
    private $cache;

    public function __construct(CacheInterface $cache)
    {
        $this->cache = $cache;
    }

    /**
     * @param string $cacheKey
     * @param int $ttl
     * @param Closure $requestCaller
     * @return mixed
     * @throws \Psr\SimpleCache\InvalidArgumentException
     * @throws LicenseRequestException
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
            } catch (LicenseRequestException $e) {
                return $entity->value;
            }
        }

        return $entity->value;
    }

    /**
     * @param string $cacheKey
     * @param callable $requestCaller
     * @return mixed
     * @throws LicenseRequestException
     * @throws \Psr\SimpleCache\InvalidArgumentException
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
     * @throws LicenseRequestException
     */
    protected function fetch($requestCaller)
    {
        $response = call_user_func($requestCaller);

        if ($response === null) {
            throw new LicenseRequestException('Could not connect to the license server.');
        }

        return $response;
    }
}
