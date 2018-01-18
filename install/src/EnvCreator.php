<?php
namespace Install;

class EnvCreator
{
    private $path = SCRIPT_ROOT . "confidential/.env";

    public function create($host, $db, $user, $password)
    {
        file_put_contents($this->path, $this->getContent($host, $db, $user, $password));
        chmod($this->path, 0777);
    }

    protected function getContent($host, $db, $user, $password)
    {
        return "DB_HOST=$host" . PHP_EOL .
            "DB_DATABASE=$db" . PHP_EOL .
            "DB_USERNAME=$user" . PHP_EOL .
            "DB_PASSWORD=$password" . PHP_EOL;
    }
}