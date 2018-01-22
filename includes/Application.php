<?php
namespace App;

use App\Providers\HeartServiceProvider;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

class Application extends Container
{
    protected $providers = [
        HeartServiceProvider::class,
    ];

    public function __construct()
    {
        static::setInstance($this);
        $this->registerBindings();
        $this->bootstrap();
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
            $db = new Database(
                getenv('DB_HOST'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                getenv('DB_DATABASE')
            );
            $db->query("SET NAMES utf8");

            return $db;
        });
    }

    protected function loadEnvironmentVariables()
    {
        try {
            (new Dotenv(SCRIPT_ROOT . "confidential"))->load();
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
}
