<?php
namespace App;

class Version
{
    /** @var Application */
    protected $app;

    /** @var Requester */
    protected $requester;

    public function __construct(Application $application, Requester $requester)
    {
        $this->requester = $requester;
        $this->app = $application;
    }

    public function getNewestWeb()
    {
        $response = $this->requester->get('https://api.github.com/repos/gammerce/sklep-sms/releases/latest');
        $decoded = json_decode($response, true);

        return array_get($decoded, 'tag_name');
    }

    public function isUpToDate()
    {
        return $this->app->version() === $this->getNewestWeb();
    }
}
