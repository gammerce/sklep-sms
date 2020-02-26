<?php
namespace App\Http\Middlewares;

use App\System\Settings;
use Closure;
use Symfony\Component\HttpFoundation\Request;

class LoadSettings implements MiddlewareContract
{
    /** @var Settings */
    private $settings;

    public function __construct(Settings $settings)
    {
        $this->settings = $settings;
    }

    public function handle(Request $request, $args, Closure $next)
    {
        $this->settings->load();
        return $next($request);
    }
}
