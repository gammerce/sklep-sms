<?php
namespace App\Providers;

use App\System\Application;
use App\System\ExternalConfigProvider;
use Sentry;
use Sentry\ClientInterface;
use Sentry\SentrySdk;

class SentryServiceProvider
{
    public function register(Application $app)
    {
        if (!is_testing() && class_exists(ClientInterface::class)) {
            $app->singleton(ClientInterface::class, function (Application $app) {
                /** @var ExternalConfigProvider $configProvider */
                $configProvider = $app->make(ExternalConfigProvider::class);

                Sentry\init([
                    "dsn" => getenv("SENTRY_DSN") ?: $configProvider->sentryDSN(),
                    "release" => $app->version(),
                    "traces_sampler" => 1.0,
                ]);

                return SentrySdk::getCurrentHub()->getClient();
            });
        }
    }
}
