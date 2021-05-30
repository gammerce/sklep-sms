<?php
namespace App\System;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\RequestException;
use App\Requesting\Requester;
use App\Routing\UrlGenerator;
use App\Support\Meta;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class License
{
    const CACHE_TTL = 10 * 60;

    private Translator $langShop;
    private Settings $settings;
    private Requester $requester;
    private CachingRequester $cachingRequester;
    private UrlGenerator $urlGenerator;
    private Meta $meta;

    private ?string $identifier;
    private ?int $expiresAt;
    private ?string $footer;

    private ?LicenseRequestException $loadingException;

    public function __construct(
        TranslationManager $translationManager,
        Settings $settings,
        Requester $requester,
        CachingRequester $cachingRequester,
        UrlGenerator $urlGenerator,
        Meta $meta
    ) {
        $this->langShop = $translationManager->shop();
        $this->settings = $settings;
        $this->requester = $requester;
        $this->cachingRequester = $cachingRequester;
        $this->urlGenerator = $urlGenerator;
        $this->meta = $meta;
    }

    /**
     * @throws LicenseRequestException
     */
    public function validate(): void
    {
        try {
            $response = $this->loadLicense();
        } catch (LicenseRequestException $e) {
            $this->loadingException = $e;
            throw $e;
        }

        $this->identifier = array_get($response, "identifier");
        $this->expiresAt = array_get($response, "expires_at");
        $this->footer = array_get($response, "f");
    }

    public function isValid(): bool
    {
        return $this->identifier !== null;
    }

    public function getLoadingException(): ?LicenseRequestException
    {
        return $this->loadingException;
    }

    public function getExpires(): string
    {
        if ($this->isForever()) {
            return $this->langShop->t("never");
        }

        return as_datetime_string($this->expiresAt);
    }

    public function getIdentifier(): ?string
    {
        return $this->identifier;
    }

    public function isForever(): bool
    {
        return $this->expiresAt === null;
    }

    public function getFooter(): ?string
    {
        return $this->footer;
    }

    /**
     * @return array
     * @throws LicenseRequestException
     */
    private function loadLicense()
    {
        try {
            return $this->cachingRequester->load(
                CacheEnum::LICENSE,
                static::CACHE_TTL,
                fn() => $this->request()
            );
        } catch (RequestException $e) {
            throw new LicenseRequestException(null, $e);
        }
    }

    /**
     * @return array
     * @throws LicenseRequestException
     */
    private function request()
    {
        $shopUrl = $this->urlGenerator->getShopUrl();

        $response = $this->requester->post(
            "https://license.sklep-sms.pl/v1/authorization/web",
            [
                "url" => $shopUrl,
                "name" => $this->settings["shop_name"] ?: $shopUrl,
                "version" => $this->meta->getVersion(),
                "language" => $this->langShop->getCurrentLanguage(),
                "php_version" => PHP_VERSION,
            ],
            [
                "Authorization" => "Bearer {$this->settings->getLicenseToken()}",
            ]
        );

        if (!$response) {
            throw new LicenseRequestException();
        }

        if (!$response->isOk()) {
            throw new LicenseRequestException($response);
        }

        return $response->json();
    }
}
