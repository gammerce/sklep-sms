<?php
namespace App\Middlewares;

use App\Application;
use App\ShopState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireNotInstalled implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        if (ShopState::isInstalled()) {
            return new Response('Shop has been installed already.');
        }

        return null;
    }
}
