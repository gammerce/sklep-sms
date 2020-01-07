<?php
namespace App\Install;

use App\System\FileSystemContract;
use App\System\Path;

class EnvCreator
{
    /** @var Path */
    private $path;

    /** @var FileSystemContract */
    private $fileSystem;

    public function __construct(Path $path, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    public function create($host, $port, $db, $user, $password)
    {
        $path = $this->path->to('confidential/.env');
        $content = $this->getContent($host, $port, $db, $user, $password);
        $this->fileSystem->put($path, $content);
        $this->fileSystem->setPermissions($path, 0777);
    }

    private function getContent($host, $port, $db, $user, $password)
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
}
