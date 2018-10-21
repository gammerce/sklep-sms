<?php
namespace App\Middlewares;

use App\Application;
use App\Settings;
use Symfony\Component\HttpFoundation\Request;

class LoadSettings implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var Settings $settings */
        $settings = $app->make(Settings::class);
        $settings['shop_url'] = $request->getUri();
        $settings->load();

        return null;
    }
}