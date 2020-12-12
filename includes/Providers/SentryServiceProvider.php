<?php
namespace App\Providers;

use App\System\Application;
use App\System\ExternalConfigProvider;
use Sentry;

class SentryServiceProvider
{
    public function boot(Application $app, ExternalConfigProvider $configProvider)
    {
        if (!is_testing()) {
            Sentry\init([
                "dsn" => getenv("SENTRY_DSN") ?: $configProvider->sentryDSN(),
                "release" => $app->version(),
                "traces_sample_rate" => 1.0,
            ]);
        }
    }
}
