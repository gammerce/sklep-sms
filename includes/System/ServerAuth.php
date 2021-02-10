<?php
namespace App\System;

use App\Models\Server;

class ServerAuth
{
    private ?Server $server = null;

    public function server(): ?Server
    {
        return $this->server;
    }

    public function setServer(Server $server = null): void
    {
        $this->server = $server;
    }

    public function check(): bool
    {
        return $this->server !== null;
    }
}
