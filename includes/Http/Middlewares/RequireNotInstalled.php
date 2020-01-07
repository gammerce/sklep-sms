<?php
namespace App\Http\Middlewares;

use App\Install\ShopState;
use App\System\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireNotInstalled implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var ShopState $shopState */
        $shopState = $app->make(ShopState::class);

        if ($shopState->isInstalled()) {
            return new Response('Shop has been installed already.');
        }

        return null;
    }
}
