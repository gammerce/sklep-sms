<?php
namespace App\Translation;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleCookieService
{
    const COOKIE_KEY = "language";

    public function setLocale(Response $response, $language)
    {
        $response->headers->setCookie(
            new Cookie(LocaleCookieService::COOKIE_KEY, $language, 0, "/", null, false, false)
        );
    }

    public function getLocale(Request $request)
    {
        return $request->cookies->get(LocaleCookieService::COOKIE_KEY);
    }
}
