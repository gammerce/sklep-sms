<?php
namespace Install;

use App\Application;

class EnvCreator
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function create($host, $db, $user, $password)
    {
        $path = $this->path();

        file_put_contents($path, $this->getContent($host, $db, $user, $password));
        chmod($path, 0777);
    }

    protected function getContent($host, $db, $user, $password)
    {
        return "DB_HOST=$host" . PHP_EOL .
            "DB_DATABASE=$db" . PHP_EOL .
            "DB_USERNAME=$user" . PHP_EOL .
            "DB_PASSWORD=$password" . PHP_EOL;
    }

    protected function path()
    {
        return $this->app->path('confidential/.env');
    }
}