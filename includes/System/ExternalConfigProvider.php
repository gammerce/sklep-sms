<?php
namespace App\System;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Exceptions\RequestException;
use App\Requesting\Requester;

class ExternalConfigProvider
{
    const CACHE_TTL = 120 * 60;

    /** @var array */
    private $config;

    /** @var Requester */
    private $requester;

    /** @var CachingRequester */
    private $cachingRequester;

    /** @var Settings */
    private $settings;

    public function __construct(
        Requester $requester,
        CachingRequester $cachingRequester,
        Settings $settings
    ) {
        $this->requester = $requester;
        $this->cachingRequester = $cachingRequester;
        $this->settings = $settings;
    }

    public function sentryDSN()
    {
        return $this->getConfig("sentry_dsn");
    }

    public function getConfig($key)
    {
        if (!$this->fetched()) {
            $this->config = $this->loadConfig();
        }

        return array_get($this->config, $key);
    }

    private function loadConfig()
    {
        try {
            return $this->cachingRequester->load(
                CacheEnum::EXTERNAL_CONFIG,
                static::CACHE_TTL,
                function () {
                    return $this->request();
                }
            );
        } catch (RequestException $e) {
            return [];
        }
    }

    private function request()
    {
        $response = $this->requester->get(
            "https://license.sklep-sms.pl/config",
            [],
            [
                "Authorization" => $this->settings->getLicenseToken(),
            ]
        );
        return $response ? $response->json() : null;
    }

    private function fetched()
    {
        return $this->config !== null;
    }
}
