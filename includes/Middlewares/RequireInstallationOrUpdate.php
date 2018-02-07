<?php
namespace App\Middlewares;

use App\Application;
use App\ShopState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireInstallationOrUpdate implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        if (ShopState::isInstalled() && $app->make(ShopState::class)->isUpToDate()) {
            return new Response('Shop is up to date');
        }

        return null;
    }
}
