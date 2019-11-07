<?php
namespace App\Install;

use App\System\Path;

class EnvCreator
{
    /** @var Path */
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function create($host, $port, $db, $user, $password)
    {
        $path = $this->path();

        file_put_contents($path, $this->getContent($host, $port, $db, $user, $password));
        chmod($path, 0777);
    }

    protected function getContent($host, $port, $db, $user, $password)
    {
        return "DB_HOST=$host" .
            PHP_EOL .
            "DB_PORT=$port" .
            PHP_EOL .
            "DB_DATABASE=$db" .
            PHP_EOL .
            "DB_USERNAME=$user" .
            PHP_EOL .
            "DB_PASSWORD=$password" .
            PHP_EOL;
    }

    protected function path()
    {
        return $this->path->to('confidential/.env');
    }
}
