<?php
namespace App\License;

use App\Requesting\Requester;

class ProxyManagerClient
{
    private Requester $requester;

    public function __construct(Requester $requester)
    {
        $this->requester = $requester;
    }

    /**
     * Reloads nginx configuration with custom sklep sms domains
     */
    public function reload()
    {
        $this->requester->post("http://127.0.0.1:8080/proxy-restart");
    }
}
