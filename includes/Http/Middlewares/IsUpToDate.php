<?php
namespace App\Http\Middlewares;

use App\Install\ShopState;
use App\Routing\UrlGenerator;
use App\System\Application;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

class IsUpToDate implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var ShopState $shopState */
        $shopState = $app->make(ShopState::class);

        /** @var UrlGenerator $url */
        $url = $app->make(UrlGenerator::class);

        if (!$shopState->isInstalled() || !$shopState->isUpToDate()) {
            return new RedirectResponse($url->to('/setup'));
        }

        return null;
    }
}
