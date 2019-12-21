<?php
namespace App\System;

use App\Providers\AppServiceProvider;
use App\Providers\HeartServiceProvider;
use App\Providers\SentryServiceProvider;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

class Application extends Container
{
    const VERSION = '3.9.1';

    private $providers = [
        AppServiceProvider::class,
        HeartServiceProvider::class,
        SentryServiceProvider::class,
    ];

    /** @var string */
    private $basePath;

    /** @var bool */
    private $isAdminSession = false;

    public function __construct($basePath)
    {
        $this->basePath = $basePath;

        static::setInstance($this);
        $this->registerBindings();
        $this->bootstrap();
    }

    public function version()
    {
        return self::VERSION;
    }

    protected function registerBindings()
    {
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
        $this->bind(Path::class, function () {
            return new Path($this->basePath);
        });
    }

    protected function bootstrap()
    {
        $this->loadEnvironmentVariables();
        $this->registerServiceProviders();
        $this->bootServiceProviders();
    }

    protected function loadEnvironmentVariables()
    {
        /** @var Path $path */
        $path = $this->make(Path::class);

        try {
            (new Dotenv($path->to('confidential')))->load();
        } catch (InvalidPathException $e) {
            //
        }
    }

    protected function registerServiceProviders()
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'register')) {
                $this->call("$provider@register");
            }
        }
    }

    protected function bootServiceProviders()
    {
        foreach ($this->providers as $provider) {
            if (method_exists($provider, 'boot')) {
                $this->call("$provider@boot");
            }
        }
    }

    public function terminate()
    {
        //
    }

    public function setAdminSession($value = true)
    {
        $this->isAdminSession = $value;
    }

    public function isAdminSession()
    {
        return $this->isAdminSession;
    }

    public function isDebug()
    {
        $debug = getenv('APP_DEBUG');

        return $debug === '1' || $debug === 'true' || $debug === 1;
    }

    public function isTesting()
    {
        return getenv('APP_ENV') === 'testing';
    }
}
