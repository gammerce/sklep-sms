<?php
namespace App\Install;

use App\Path;

class RequirementsStore
{
    /** @var Path */
    private $path;

    public function __construct(Path $path)
    {
        $this->path = $path;
    }

    public function getModules()
    {
        return [
            [
                'text' => "PHP v5.6.0 lub wyższa",
                'value' => PHP_VERSION_ID >= 50600,
                'must-be' => false,
            ],

            [
                'text' => "Moduł cURL",
                'value' => function_exists('curl_version'),
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
            if (!is_writable($fullPath)) {
                return false;
            }
        }

        foreach ($this->getFilesToDelete() as $path) {
            $fullPath = $this->path->to($path);
            if (file_exists($fullPath)) {
                return false;
            }
        }

        return true;
    }
}
