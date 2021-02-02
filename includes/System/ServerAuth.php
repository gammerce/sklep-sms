<?php
namespace App\System;

use App\Models\Server;

class ServerAuth
{
    private ?Server $server = null;

    /**
     * @return Server|null
     */
    public function server()
    {
        return $this->server;
    }

    public function setServer(Server $server = null)
    {
        $this->server = $server;
    }

    public function check()
    {
        return $this->server !== null;
    }
}
