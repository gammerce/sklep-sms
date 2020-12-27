<?php
namespace App\Http;

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

    public function expectsJson(): bool
    {
        return ($this->ajax() && $this->acceptsAnyContentType()) || $this->wantsJson();
    }

    public function ajax(): bool
    {
        return "XMLHttpRequest" === $this->request->headers->get("X-Requested-With");
    }

    public function acceptsAnyContentType(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();

        return count($acceptable) === 0 ||
            (isset($acceptable[0]) && ($acceptable[0] === "*/*" || $acceptable[0] === "*"));
    }

    /**
     * @return string[]
     */
    public function getAcceptableContentTypes()
    {
        return array_keys(AcceptHeader::fromString($this->request->headers->get("Accept"))->all());
    }

    public function isFromServer(): bool
    {
        return is_server_platform($this->request->headers->get("User-Agent"));
    }

    public function isAdminSession(): bool
    {
        $session = $this->request->getSession();
        return $session && $session->getName() === "admin";
    }

    private function wantsJson(): bool
    {
        $acceptable = $this->getAcceptableContentTypes();
        return isset($acceptable[0]) &&
            (str_contains($acceptable[0], "/json") || str_contains($acceptable[0], "+json"));
    }

    public function acceptsNewFormat(Request $request): bool
    {
        return $request->headers->get("Accept-version") === "v2";
    }
}
