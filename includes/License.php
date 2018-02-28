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

    /** @var Requester */
    protected $requester;

    public function __construct(Translator $translator, Settings $settings, Requester $requester)
    {
        $this->lang = $translator;
        $this->settings = $settings;
        $this->requester = $requester;
    }

    public function validate()
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

    protected function request()
    {
        $response = $this->requester->get('http://license.sklep-sms.pl/license.php', [
            'action'   => 'login_web',
            'lid'      => $this->settings['license_login'],
            'lpa'      => $this->settings['license_password'],
            'name'     => $this->settings['shop_url'],
            'version'  => app()->version(),
            'language' => $this->lang->getCurrentLanguage(),
        ]);

        return json_decode($response, true);
    }
}