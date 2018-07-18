<?php
namespace App\Providers;

use App\Application;
use Raven_Client;

class SentryServiceProvider
{
    public function register(Application $app)
    {
        $dsn = getenv('SENTRY_DSN');

        if (class_exists(Raven_Client::class) && strlen($dsn)) {
            $app->singleton(Raven_Client::class, function () use ($dsn) {
                return new Raven_Client($dsn);
            });
        }
    }
}
