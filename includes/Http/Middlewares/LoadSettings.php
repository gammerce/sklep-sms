<?php
namespace App\Http\Middlewares;

use App\System\Application;
use App\System\Settings;
use Symfony\Component\HttpFoundation\Request;

class LoadSettings implements MiddlewareContract
{
    public function handle(Request $request, Application $app, $args = null)
    {
        /** @var Settings $settings */
        $settings = $app->make(Settings::class);
        $settings->load();

        return null;
    }
}
