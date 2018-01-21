<?php
namespace App;

use Database;
use Dotenv\Dotenv;
use Dotenv\Exception\InvalidPathException;
use Illuminate\Container\Container;

class Application extends Container
{
    public function __construct()
    {
        $this->registerBaseBindings();
    }

    protected function registerBaseBindings()
    {
        static::setInstance($this);

        $this->instance('app', $this);
        $this->instance(Container::class, $this);
        $this->registerDatabase();
        $this->loadEnvironmentVariables();
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
}