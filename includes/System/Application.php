<?php
namespace App\System;

use App\Providers\AppServiceProvider;
use App\Providers\HeartServiceProvider;
use App\Providers\SentryServiceProvider;
use App\Support\Path;
use DirectoryIterator;
use Illuminate\Container\Container;
use Sentry\SentrySdk;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Application extends Container
{
    private array $providers = [
        AppServiceProvider::class,
        HeartServiceProvider::class,
        SentryServiceProvider::class,
    ];

    private string $basePath;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        static::setInstance($this);
        $this->registerBindings();
        $this->bootstrap();
    }

    private function registerBindings(): void
    {
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
        $this->bind(Path::class, fn() => new Path(realpath($this->basePath)));
    }

    private function bootstrap(): void
    {
        $this->getProviders();
        $this->registerServiceProviders();
        $this->bootServiceProviders();
    }

    private function registerServiceProviders(): void
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, "register")) {
                $this->call("$provider@register");
            }
        }
    }

    private function bootServiceProviders(): void
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, "boot")) {
                $this->call("$provider@boot");
            }
        }
    }

    public function terminate(Request $request = null, Response $response = null): void
    {
        if (class_exists(SentrySdk::class)) {
            $transaction = SentrySdk::getCurrentHub()->getTransaction();
            if ($transaction && $response) {
                $transaction->setHttpStatus($response->getStatusCode());
                $transaction->finish();
            }
        }
    }

    private function getProviders(): array
    {
        if (!$this->providers) {
            /** @var Path $path */
            $path = $this->make(Path::class);

            $dir = new DirectoryIterator($path->to("/includes/Providers"));
            foreach ($dir as $fileInfo) {
                if (str_ends_with($fileInfo->getFilename(), ".php")) {
                    $fileName = $fileInfo->getBasename(".php");
                    $providerClassName = "App\\Providers\\{$fileName}";

                    if (!in_array($providerClassName, $this->providers)) {
                        $this->providers[] = $providerClassName;
                    }
                }
            }
        }

        return $this->providers;
    }
}
