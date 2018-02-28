<?php
namespace App;

class Version
{
    /** @var Requester */
    protected $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    public function getNewestWeb()
    {
        $response = $this->requester->get('https://api.github.com/repos/gammerce/sklep-sms/releases/latest');
        $decoded = json_decode($response, true);

        return array_get($decoded, 'tag_name');
    }
}
