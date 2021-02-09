<?php
namespace App\Providers;

use App\Support\Meta;
use App\System\Application;
use App\System\ExternalConfigProvider;
use Sentry;
use Sentry\State\Scope;

class SentryServiceProvider
{
    public function register(Application $app)
    {
        if (!is_testing() && class_exists(\Raven_Client::class)) {
            $app->singleton(\Raven_Client::class, function (Application $app) {
                /** @var ExternalConfigProvider $configProvider */
                $configProvider = $app->make(ExternalConfigProvider::class);

                /** @var Meta $meta */
                $meta = $app->make(Meta::class);

                $client = new \Raven_Client([
                    "dsn" => getenv("SENTRY_DSN") ?: $configProvider->sentryDSN(),
                    "release" => $meta->getVersion(),
                ]);

                $client->tags_context([
                    "build" => $meta->getBuild(),
                ]);

                return $client;
            });
        }
    }

    public function boot(Meta $meta, ExternalConfigProvider $configProvider)
    {
        if (!is_testing() && class_exists(Sentry\SentrySdk::class)) {
            Sentry\init([
                "dsn" => getenv("SENTRY_DSN") ?: $configProvider->sentryDSN(),
                "release" => $meta->getVersion(),
                "traces_sample_rate" => $configProvider->sentrySampleRate() ?: 1.0,
                "send_default_pii" => true,
            ]);

            Sentry\configureScope(function (Scope $scope) use ($meta) {
                $scope->setTag("build", $meta->getBuild());
            });
        }
    }
}
