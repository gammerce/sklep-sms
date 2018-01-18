<?php
namespace App;

class Version
{
    public function getNewestWeb()
    {
        $response = json_decode(
            curl_get_contents('https://api.github.com/repos/gammerce/sklep-sms/releases/latest'), true
        );

        return array_get($response, 'tag_name');
    }
}