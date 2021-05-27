<?php
namespace App\Http\Middlewares;

use App\Install\ShopState;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireInstalledAndNotUpdated implements MiddlewareContract
{
    private ShopState $shopState;

    public function __construct(ShopState $shopState)
    {
        $this->shopState = $shopState;
    }

    public function handle(Request $request, $args, Closure $next): Response
    {
        if (!$this->shopState->isInstalled()) {
            return new Response("Shop needs to be installed first");
        }

        if ($this->shopState->isUpToDate()) {
            return new Response("Shop does not need updating");
        }

        return $next($request);
    }
}
