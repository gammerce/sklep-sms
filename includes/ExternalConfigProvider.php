<?php
namespace App;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Requesting\Requester;

class ExternalConfigProvider
{
    const CACHE_TTL = 120 * 60;

    /** @var array */
    protected $config;

    /** @var Requester */
    protected $requester;

    /** @var CachingRequester */
    protected $cachingRequester;

    public function __construct(Requester $requester, CachingRequester $cachingRequester)
    {
        $this->requester = $requester;
        $this->cachingRequester = $cachingRequester;
        $this->config = [];
    }

    public function getConfig($key)
    {
        if (!$this->fetched()) {
            $this->config = $this->loadConfig();
        }

        return array_get($this->config, $key);
    }

    public function sentryDSN()
    {
        return $this->getConfig('sentry_dsn');
    }

    protected function loadConfig()
    {
        return $this->cachingRequester->load(CacheEnum::EXTERNAL_CONFIG, static::CACHE_TTL, function () {
            return $this->request();
        });
    }

    protected function request()
    {
        $response = $this->requester->get('http://license.sklep-sms.pl/config');
        return $response ? $response->json() : null;
    }

    protected function fetched()
    {
        return !empty($this->config);
    }
}
