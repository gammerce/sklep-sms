<?php
namespace App;

use App\Requesting\Requester;

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
        $content = $response->json();

        return array_get($content, 'tag_name');
    }

    public function isUpToDate()
    {
        return $this->app->version() === $this->getNewestWeb();
    }
}
