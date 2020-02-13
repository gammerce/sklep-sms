<?php
namespace App\System;

use App\Models\Server;

class ServerAuth
{
    /** @var Server|null */
    private $server;

    /**
     * @return Server|null
     */
    public function server()
    {
        return $this->server;
    }

    public function setServer(Server $server = null)
    {
        $this->$server = $server;
    }

    public function check()
    {
        return $this->server !== null;
    }
}
