<?php
namespace App;

use App\Exceptions\LicenseException;

class License
{
    /** @var Translator */
    protected $lang;

    /** @var Settings */
    protected $settings;

    /** @var string */
    protected $message;

    /** @var string */
    protected $expires;

    /** @var string */
    protected $page;

    /** @var string */
    protected $footer;

    public function __construct(Translator $translator, Settings $settings)
    {
        $this->lang = $translator;
        $this->settings = $settings;

        $this->validate();
    }

    public function isValid()
    {
        return $this->message === "logged_in";
    }

    public function getExpires()
    {
        if ($this->isForever()) {
            return $this->lang->translate('never');
        }

        return date($this->settings['date_format'], $this->expires);
    }

    public function isForever()
    {
        return $this->expires == -1;
    }

    public function getPage()
    {
        return $this->page;
    }

    public function getFooter()
    {
        return $this->footer;
    }

    protected function validate()
    {
        $response = $this->request();

        if ($response === null || !isset($response['text'])) {
            throw new LicenseException();
        }

        $this->message = $response['text'];
        $this->expires = array_get($response, 'expire');
        $this->page = array_get($response, 'page');
        $this->footer = array_get($response, 'f');
    }

    protected function request()
    {
        $url = 'http://license.sklep-sms.pl/license.php' .
            '?action=login_web' .
            '&lid=' . urlencode($this->settings['license_login']) .
            '&lpa=' . urlencode($this->settings['license_password']) .
            '&name=' . urlencode($this->settings['shop_url']) .
            '&version=' . VERSION .
            '&language=' . $this->lang->getCurrentLanguage();

        $response = curl_get_contents($url);

        return json_decode($response, true);
    }
}