<?php
namespace App\Middlewares;

use App\Application;
use App\Settings;
use Raven_Client;
use Symfony\Component\HttpFoundation\Request;

class LoadSettings implements MiddlewareContract
{
    public function handle(Request $request, Application $app)
    {
        /** @var Settings $settings */
        $settings = $app->make(Settings::class);
        $settings->load();

        /** @var Raven_Client $ravenClient */
        $ravenClient = $app->make(Raven_Client::class);
        $ravenClient->tags_context([
            'license_id' => $settings['license_login'],
        ]);

        return null;
    }
}