<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\Install\ShopState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireNotInstalledOrNotUpdated implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        if (ShopState::isInstalled() && $app->make(ShopState::class)->isUpToDate()) {
            return new Response('Shop is up to date');
        }

        return null;
    }
}
