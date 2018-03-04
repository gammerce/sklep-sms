<?php
namespace App;

use App\Providers\HeartServiceProvider;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

class Application extends Container
{
    const VERSION = '3.4.1';

    protected $providers = [
        HeartServiceProvider::class,
    ];

    /** @var string */
    protected $basePath;

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
        $this->registerDatabase();
        $this->singleton(Heart::class);
        $this->singleton(Auth::class);
        $this->singleton(Settings::class);
        $this->singleton(CurrentPage::class);
        $this->singleton(License::class);
        $this->singleton(TranslationManager::class);
        $this->registerServiceProviders();
        $this->bootServiceProviders();
    }

    protected function registerDatabase()
    {
        $this->singleton(Database::class, function () {
            return new Database(
                getenv('DB_HOST'),
                getenv('DB_PORT'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                getenv('DB_DATABASE')
            );
        });
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
}
