<?php
namespace App\Middlewares;

use App\Application;
use App\ShopState;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireInstalledAndNotUpdated implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        if (!ShopState::isInstalled()) {
            return new Response('Shop needs to be installed first');
        }

        /** @var ShopState $shopState */
        $shopState = $app->make(ShopState::class);

        if ($shopState->isUpToDate()) {
            return new Response('Shop does not need updating');
        }

        return null;
    }
}
