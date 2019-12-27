<?php
namespace App\System;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\RequestException;
use App\Requesting\Requester;
use App\Routes\UrlGenerator;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class License
{
    const CACHE_TTL = 10 * 60;

    /** @var Translator */
    private $langShop;

    /** @var Settings */
    private $settings;

    /** @var Requester */
    private $requester;

    /** @var CachingRequester */
    private $cachingRequester;

    /** @var UrlGenerator */
    private $urlGenerator;

    /** @var int */
    private $externalLicenseId;

    /** @var int */
    private $expiresAt;

    /** @var string */
    private $footer;

    /** @var LicenseRequestException */
    private $loadingException;

    public function __construct(
        TranslationManager $translationManager,
        Settings $settings,
        Requester $requester,
        CachingRequester $cachingRequester,
        UrlGenerator $urlGenerator
    ) {
        $this->langShop = $translationManager->shop();
        $this->settings = $settings;
        $this->requester = $requester;
        $this->cachingRequester = $cachingRequester;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @throws LicenseRequestException
     */
    public function validate()
    {
        try {
            $response = $this->loadLicense();
        } catch (LicenseRequestException $e) {
            $this->loadingException = $e;
            throw $e;
        }

        $this->externalLicenseId = array_get($response, 'id');
        $this->expiresAt = array_get($response, 'expires_at');
        $this->footer = array_get($response, 'f');
    }

    public function isValid()
    {
        return $this->externalLicenseId !== null;
    }

    public function getLoadingException()
    {
        return $this->loadingException;
    }

    public function getExpires()
    {
        if ($this->isForever()) {
            return $this->langShop->translate('never');
        }

        return date($this->settings['date_format'], $this->expiresAt);
    }

    public function getExternalId()
    {
        return $this->externalLicenseId;
    }

    public function isForever()
    {
        return $this->expiresAt === null;
    }

    public function getFooter()
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
                function () {
                    return $this->request();
                }
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
            'http://license.nalunch.com/v1/authorization/web',
            [
                'url' => $shopUrl,
                'name' => $this->settings['shop_name'] ?: $shopUrl,
                'version' => app()->version(),
                'language' => $this->langShop->getCurrentLanguage(),
                'php_version' => PHP_VERSION,
            ],
            [
                'Authorization' => $this->settings['license_password'],
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
