<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\Path;

class EnvCreator
{
    private Path $path;
    private FileSystemContract $fileSystem;

    public function __construct(Path $path, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
    }

    public function create($host, $port, $db, $user, $password)
    {
        $path = $this->path->to("confidential/.env");
        $content = $this->getContent($host, $port, $db, $user, $password);
        $this->fileSystem->put($path, $content);
        $this->fileSystem->setPermissions($path, 0777);
    }

    private function getContent($host, $port, $db, $user, $password)
    {
        return <<<EOL
DB_HOST=$host
DB_PORT=$port
DB_DATABASE=$db
DB_USERNAME=$user
DB_PASSWORD=$password

MAIL_HOST=
MAIL_PASSWORD=
EOL;
    }
}
