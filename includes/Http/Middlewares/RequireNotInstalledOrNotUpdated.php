<?php
namespace App\Http\Middlewares;

use App\Install\ShopState;
use App\System\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireNotInstalledOrNotUpdated implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var ShopState $shopState */
        $shopState = $app->make(ShopState::class);

        if ($shopState->isInstalled() && $shopState->isUpToDate()) {
            return new Response('Shop is up to date');
        }

        return null;
    }
}
