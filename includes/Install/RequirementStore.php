<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\Meta;
use App\Support\Path;

class RequirementStore
{
    private Path $path;
    private FileSystemContract $fileSystem;
    private Meta $meta;

    public function __construct(Path $path, Meta $meta, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->fileSystem = $fileSystem;
        $this->meta = $meta;
    }

    public function getModules(): array
    {
        $buildPHPVersion = $this->getBuildPHPVersion();

        return [
            [
                "text" => "PHP v{$buildPHPVersion} lub wyżej",
                "value" => version_compare(PHP_VERSION, $buildPHPVersion) >= 0,
                "required" => false,
            ],
            [
                "text" => "Moduł CURL",
                "value" => extension_loaded("curl"),
                "required" => true,
            ],
            [
                "text" => "Moduł PDO",
                "value" => extension_loaded("pdo") && extension_loaded("pdo_mysql"),
                "required" => true,
            ],
        ];
    }

    public function getFilesWithWritePermission(): array
    {
        return [
            "confidential/",
            "data/",
            "data/cache/",
            "data/logs/",
            "data/transactions/",
            "themes/fusion/shop/services/",
        ];
    }

    public function getFilesToDelete(): array
    {
        return [
            "errors",
            "images",
            "install",
            "jscripts",
            "styles",
            "admin.php",
            "cron.php",
            "extra_stuff.php",
            "js.php",
            "jsonhttp.php",
            "jsonhttp_admin.php",
            "servers_stuff.php",
            "transfer_finalize.php",
        ];
    }

    public function areFilesInCorrectState(): bool
    {
        foreach ($this->getFilesWithWritePermission() as $path) {
            $fullPath = $this->path->to($path);
            if (!$this->fileSystem->isWritable($fullPath)) {
                return false;
            }
        }

        foreach ($this->getFilesToDelete() as $path) {
            $fullPath = $this->path->to($path);
            if ($this->fileSystem->exists($fullPath)) {
                return false;
            }
        }

        return true;
    }

    private function getBuildPHPVersion(): string
    {
        return str_replace("php", "", $this->meta->getBuild());
    }
}
