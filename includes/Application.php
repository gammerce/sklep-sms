<?php
namespace App;

use App\Providers\AppServiceProvider;
use App\Providers\HeartServiceProvider;
use App\Providers\SentryServiceProvider;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

class Application extends Container
{
    const VERSION = '3.6.5';

    protected $providers = [
        AppServiceProvider::class,
        HeartServiceProvider::class,
        SentryServiceProvider::class
    ];

    /** @var string */
    protected $basePath;

    /** @var bool */
    protected $isAdminSession = false;

    public function __construct($basePath)
    {
        $this->setBasePath($basePath);

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
    }

    protected function bootstrap()
    {
        $this->loadEnvironmentVariables();
        $this->registerServiceProviders();
        $this->bootServiceProviders();
    }

    protected function loadEnvironmentVariables()
    {
        try {
            (new Dotenv($this->path('confidential')))->load();
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

    public function setBasePath($basePath)
    {
        $this->basePath = rtrim($basePath, '\/');
    }

    public function path($path = '')
    {
        if (!strlen($path)) {
            return $this->basePath;
        }

        if (starts_with($path, DIRECTORY_SEPARATOR)) {
            return $this->basePath . $path;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . $path;
    }

    public function sqlLogPath()
    {
        return $this->path('errors/sql.log');
    }

    public function errorsLogPath()
    {
        return $this->path('errors/errors.log');
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
