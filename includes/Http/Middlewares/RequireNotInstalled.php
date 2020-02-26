<?php
namespace App\Http\Middlewares;

use App\Install\ShopState;
use Closure;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireNotInstalled implements MiddlewareContract
{
    /** @var ShopState */
    private $shopState;

    public function __construct(ShopState $shopState)
    {
        $this->shopState = $shopState;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        if ($this->shopState->isInstalled()) {
            return new Response('Shop has been installed already.');
        }

        return $next($request);
    }
}
