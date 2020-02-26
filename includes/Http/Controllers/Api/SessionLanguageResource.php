<?php
namespace App\Http\Controllers\Api;

use App\Translation\LocaleCookieService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionLanguageResource
{
    public function put(Request $request, LocaleCookieService $localeCookieService)
    {
        $language = $request->request->get('language');

        $response = new Response();
        $localeCookieService->setLocale($response, $language);

        return $response;
    }
}
