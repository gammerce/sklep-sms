<?php
namespace App;

use App\Requesting\Requester;

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
        $content = $response ? $response->json() : null;

        return array_get($content, 'tag_name');
    }

    public function getNewestAmxmodx()
    {
        $response = $this->requester->get('https://api.github.com/repos/gammerce/plugin-amxmodx/releases/latest');
        $content = $response ? $response->json() : null;

        return array_get($content, 'tag_name');
    }

    public function getNewestSourcemod()
    {
        $response = $this->requester->get('https://api.github.com/repos/gammerce/plugin-sourcemod/releases/latest');
        $content = $response ? $response->json() : null;

        return array_get($content, 'tag_name');
    }
}
