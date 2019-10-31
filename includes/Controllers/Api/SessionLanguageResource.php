<?php
namespace App\Controllers\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SessionLanguageResource
{
    public function put(Request $request)
    {
        setcookie(
            "language",
            escape_filename($request->request->get('language')),
            time() + 86400 * 30,
            "/"
        ); // 86400 = 1 day

        return new Response();
    }
}
