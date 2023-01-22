<?php
namespace App\System;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Exceptions\RequestException;
use App\Requesting\Requester;

class ExternalConfigProvider
{
    const CACHE_TTL = 20 * 60;

    private Requester $requester;
    private CachingRequester $cachingRequester;
    private Settings $settings;
    private ?array $config = null;

    public function __construct(
        Requester $requester,
        CachingRequester $cachingRequester,
        Settings $settings
    ) {
        $this->requester = $requester;
        $this->cachingRequester = $cachingRequester;
        $this->settings = $settings;
    }

    public function sentryDSN(): string
    {
        return (string) $this->getConfig("sentry_dsn");
    }

    public function sentrySampleRate(): float
    {
        return (float) $this->getConfig("sentry_sample_rate", 0);
    }

    public function captchaSiteKey(): string
    {
        return (string) $this->getConfig("hcaptcha_sitekey");
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        if (!$this->fetched()) {
            $this->config = $this->loadConfig();
        }

        return array_get($this->config, $key, $default);
    }

    private function loadConfig(): array
    {
        try {
            return $this->cachingRequester->load(
                CacheEnum::EXTERNAL_CONFIG,
                static::CACHE_TTL,
                fn() => $this->request()
            );
        } catch (RequestException $e) {
            return [];
        }
    }

    private function request(): ?array
    {
        $response = $this->requester->get(
            "https://license.sklep-sms.pl/config",
            [],
            [
                "Authorization" => "Bearer {$this->settings->getLicenseToken()}",
            ]
        );
        return $response?->json();
    }

    private function fetched(): bool
    {
        return $this->config !== null;
    }
}
