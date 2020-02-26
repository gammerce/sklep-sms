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
        $time = time() + 86400 * 30;
        $response->headers->setCookie(
            new Cookie(LocaleCookieService::COOKIE_KEY, $language, $time)
        );
    }

    public function getLocale(Request $request)
    {
        return $request->cookies->get(LocaleCookieService::COOKIE_KEY);
    }
}
