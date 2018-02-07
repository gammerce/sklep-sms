<?php
namespace App\Middlewares;

use App\Application;
use App\Settings;
use Symfony\Component\HttpFoundation\Request;

class LoadSettings implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        $settings = $app->make(Settings::class);
        $settings->load();

        return null;
    }
}