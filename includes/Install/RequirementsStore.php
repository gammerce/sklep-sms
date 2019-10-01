<?php
namespace App\Install;

class RequirementsStore
{
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
            "errors/",
            "confidential/",
            "themes/default/services/",
            "data/",
            "data/transfers/",
            "data/cache/",
        ];
    }

    public function getFilesToDelete()
    {
        return [
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
}
