<?php
namespace App\Middlewares;

use App\Application;
use App\ShopState;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class IsUpToDate implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        if (!ShopState::isInstalled()) {
            return new RedirectResponse('/install/');
        }

        /** @var ShopState $shopState */
        $shopState = $app->make(ShopState::class);

        if (!$shopState->isUpToDate()) {
            return new RedirectResponse('/install/');
        }

        return null;
    }
}
