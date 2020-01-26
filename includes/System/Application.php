<?php
namespace App\System;

use DirectoryIterator;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

class Application extends Container
{
    const VERSION = '3.11.1';

    /** @var array */
    private $providers = [];

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

    private function registerBindings()
    {
        $this->instance(Container::class, $this);
        $this->instance(Application::class, $this);
        $this->bind(Path::class, function () {
            return new Path($this->basePath);
        });
    }

    private function bootstrap()
    {
        $this->loadEnvironmentVariables();
        $this->registerServiceProviders();
        $this->bootServiceProviders();
    }

    private function loadEnvironmentVariables()
    {
        /** @var Path $path */
        $path = $this->make(Path::class);

        try {
            (new Dotenv($path->to('confidential')))->load();
        } catch (InvalidPathException $e) {
            //
        }
    }

    private function registerServiceProviders()
    {
        foreach ($this->getProviders() as $provider) {
            if (method_exists($provider, 'register')) {
                $this->call("$provider@register");
            }
        }
    }

    private function bootServiceProviders()
    {
        foreach ($this->getProviders() as $provider) {
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

    private function getProviders()
    {
        if (!$this->providers) {
            /** @var Path $path */
            $path = $this->make(Path::class);

            $dir = new DirectoryIterator($path->to("/includes/Providers"));
            foreach ($dir as $fileInfo) {
                if (ends_at($fileInfo->getFilename(), '.php')) {
                    $fileName = $fileInfo->getBasename('.php');
                    $this->providers[] = "App\\Providers\\{$fileName}";
                }
            }
        }

        return $this->providers;
    }
}
