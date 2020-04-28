<?php
namespace App\Http;

use App\Models\Server;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class RequestHelper
{
    /** @var Request */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function expectsJson()
    {
        return ($this->ajax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    public function ajax()
    {
        return "XMLHttpRequest" === $this->request->headers->get("X-Requested-With");
    }

    public function acceptsAnyContentType()
    {
        $acceptable = $this->getAcceptableContentTypes();

        return count($acceptable) === 0 ||
            (isset($acceptable[0]) && ($acceptable[0] === "*/*" || $acceptable[0] === "*"));
    }

    public function getAcceptableContentTypes()
    {
        return array_keys(AcceptHeader::fromString($this->request->headers->get("Accept"))->all());
    }

    public function isFromServer()
    {
        return in_array(
            $this->request->headers->get("User-Agent"),
            [Server::TYPE_AMXMODX, Server::TYPE_SOURCEMOD],
            true
        );
    }

    private function wantsJson()
    {
        $acceptable = $this->getAcceptableContentTypes();
        return isset($acceptable[0]) &&
            (str_contains($acceptable[0], "/json") || str_contains($acceptable[0], "+json"));
    }
}
