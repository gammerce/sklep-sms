<?php
namespace App;

use App\Cache\CacheEnum;
use App\Cache\CachingRequester;
use App\Exceptions\LicenseRequestException;
use App\Exceptions\RequestException;
use App\Requesting\Requester;
use Symfony\Component\HttpFoundation\Request;

class License
{
    const CACHE_TTL = 10 * 60;

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

    /** @var int */
    protected $expiresAt;

    /** @var string */
    protected $footer;

    /** @var LicenseRequestException */
    protected $loadingException;

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
     * @throws LicenseRequestException
     */
    protected function loadLicense()
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
    protected function request()
    {
        $shopUrl = $this->getShopUrl();

        $response = $this->requester->post(
            'http://license.sklep-sms.pl/v1/authorization/web',
            [
                'url' => $shopUrl,
                'name' => $this->settings['shop_name'] ?: $shopUrl,
                'version' => app()->version(),
                'language' => $this->lang->getCurrentLanguage()
            ],
            [
                'Authorization' => $this->settings['license_password']
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

    private function getShopUrl()
    {
        if ($this->settings['shop_url']) {
            return $this->settings['shop_url'];
        }

        /** @var Request $request */
        $request = app()->make(Request::class);

        return $request->getSchemeAndHttpHost();
    }
}
