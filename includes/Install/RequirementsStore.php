<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\Path;

class RequirementsStore
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

    public function getModules()
    {
        return [
            [
                'text' => "PHP v5.6.0 lub wyżej",
                'value' => semantic_to_number(PHP_VERSION) >= 50600,
                'must-be' => false,
            ],
            [
                'text' => "Moduł CURL",
                'value' => extension_loaded('curl'),
                'must-be' => true,
            ],
            [
                'text' => "Moduł PDO",
                'value' => extension_loaded('pdo') && extension_loaded('pdo_mysql'),
                'must-be' => true,
            ],
        ];
    }

    public function getFilesWithWritePermission()
    {
        return [
            "confidential/",
            "data/",
            "data/cache/",
            "data/logs/",
            "data/transfers/",
            "themes/default/services/",
        ];
    }

    public function getFilesToDelete()
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

    /**
     * @return bool
     */
    public function areFilesInCorrectState()
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
}
