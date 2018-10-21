<?php
namespace App;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Exceptions\InvalidResponse;
use App\Exceptions\RequestException;
use App\Requesting\Requester;

class License
{
    const CACHE_TTL = 20 * 60;

    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    /** @var Requester */
    protected $requester;

    /** @var CachingRequester */
    protected $cachingRequester;

    /** @var int */
    protected $externalLicenseId;

    /** @var string */
    protected $expiresAt;

    /** @var string */
    protected $footer;

    public function __construct(
        Translator $translator,
        Settings $settings,
        Requester $requester,
        CachingRequester $cachingRequester
    ) {
        $this->lang = $translator;
        $this->settings = $settings;
        $this->requester = $requester;
        $this->cachingRequester = $cachingRequester;
    }

    /**
     * @throws InvalidResponse
     * @throws RequestException
     */
    public function validate()
    {
        $response = $this->loadLicense();

        $this->externalLicenseId = array_get($response, 'id');
        $this->expiresAt = array_get($response, 'expires_at');
        $this->footer = array_get($response, 'f');
    }

    public function isValid()
    {
        return $this->externalLicenseId !== null;
    }

    public function getExpires()
    {
        if ($this->isForever()) {
            return $this->lang->translate('never');
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
     * @throws InvalidResponse
     * @throws RequestException
     */
    protected function loadLicense()
    {
        return $this->cachingRequester->load(CacheEnum::LICENSE, static::CACHE_TTL, function () {
            return $this->request();
        });
    }

    /**
     * @return array
     * @throws InvalidResponse
     * @throws RequestException
     */
    protected function request()
    {
        $response = $this->requester->post(
            'http://license2.sklep-sms.pl/v1/authorization/web',
            [
                'url'      => $this->settings['shop_url'],
                'name'     => $this->settings['shop_name'] ?: $this->settings['shop_url'],
                'version'  => app()->version(),
                'language' => $this->lang->getCurrentLanguage(),
            ],
            [
                'Authorization' => $this->settings['license_password'],
            ]
        );

        if (!$response) {
            throw new RequestException();
        }

        if (!$response->isOk()) {
            throw new InvalidResponse($response);
        }

        return $response->json();
    }
}
