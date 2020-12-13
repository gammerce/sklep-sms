<?php
namespace App\Providers;

use App\System\Application;
use App\System\ExternalConfigProvider;
use Sentry;

class SentryServiceProvider
{
    public function register(Application $app)
    {
        if (!is_testing() && class_exists(\Raven_Client::class)) {
            $app->singleton(\Raven_Client::class, function (Application $app) {
                /** @var ExternalConfigProvider $configProvider */
                $configProvider = $app->make(ExternalConfigProvider::class);

                return new \Raven_Client([
                    "dsn" => getenv("SENTRY_DSN") ?: $configProvider->sentryDSN(),
                    "release" => $app->version(),
                ]);
            });
        }
    }

    public function boot(Application $app, ExternalConfigProvider $configProvider)
    {
        if (!is_testing() && class_exists(\Sentry\SentrySdk::class)) {
            Sentry\init([
                "dsn" => getenv("SENTRY_DSN") ?: $configProvider->sentryDSN(),
                "release" => $app->version(),
                "traces_sample_rate" => $configProvider->sentrySampleRate() ?: 1.0,
            ]);
        }
    }
}
