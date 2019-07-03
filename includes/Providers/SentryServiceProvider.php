<?php
namespace App\Providers;

use App\Application;
use App\ExternalConfigProvider;
use Raven_Client;

class SentryServiceProvider
{
    public function register(Application $app, ExternalConfigProvider $configProvider)
    {
        if ($app->isTesting()) {
            return;
        }

        $dsn = getenv('SENTRY_DSN') ?: $configProvider->sentryDSN();

        if (class_exists(Raven_Client::class) && strlen($dsn)) {
            $app->singleton(Raven_Client::class, function () use ($dsn, $app) {
                return new Raven_Client([
                    'dsn' => $dsn,
                    'release' => $app->version()
                ]);
            });
        }
    }
}
