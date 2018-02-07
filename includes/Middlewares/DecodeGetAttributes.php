<?php
namespace App\Middlewares;

use App\Application;
use Symfony\Component\HttpFoundation\Request;

class DecodeGetAttributes implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        foreach ($_GET as $key => $value) {
            $_GET[$key] = urldecode($value);
        }

        return null;
    }
}
