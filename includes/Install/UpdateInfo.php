<?php
namespace App\Install;

use App\Support\FileSystemContract;
use App\Support\BasePath;
use App\Theme\Template;

class UpdateInfo
{
    private BasePath $path;
    private Template $template;
    private FileSystemContract $fileSystem;

    public function __construct(BasePath $path, Template $template, FileSystemContract $fileSystem)
    {
        $this->path = $path;
        $this->template = $template;
        $this->fileSystem = $fileSystem;
    }

    public function updateInfo(&$everythingOk, $filesPriv, $filesDel, $modules)
    {
        // Let's check the modules settings
        $serverModules = "";
        foreach ($modules as $module) {
            $title = $module["text"];
            $status = $module["value"] ? "ok" : "bad";

            $serverModules .= $this->template->render(
                "setup/update/module",
                compact("title", "status")
            );

            if (!$module["value"] && $module["required"]) {
                $everythingOk = false;
            }
        }
        if (strlen($serverModules)) {
            $text = "Moduły na serwerze";
            $data = $serverModules;
            $serverModules = $this->template->render(
                "setup/update/update_info_brick",
                compact("text", "data")
            );
        }

        $filesPrivileges = "";
        foreach ($filesPriv as $file) {
            if (!strlen($file)) {
                continue;
            }

            if ($this->fileSystem->isWritable($this->path->to($file))) {
                $status = "ok";
            } else {
                $status = "bad";
                $everythingOk = false;
            }

            $filesPrivileges .= $this->template->render(
                "setup/update/file",
                compact("file", "status")
            );
        }
        if (strlen($filesPrivileges)) {
            $text = "Uprawnienia do zapisu";
            $data = $filesPrivileges;
            $filesPrivileges = $this->template->render(
                "setup/update/update_info_brick",
                compact("text", "data")
            );
        }

        $filesDelete = "";
        foreach ($filesDel as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (!$this->fileSystem->exists($this->path->to($file))) {
                $status = "ok";
            } else {
                $status = "bad";
                $everythingOk = false;
            }

            $filesDelete .= $this->template->render("setup/update/file", compact("file", "status"));
        }
        if (strlen($filesDelete)) {
            $text = "Pliki do usunięcia";
            $data = $filesDelete;
            $filesDelete = $this->template->render(
                "setup/update/update_info_brick",
                compact("text", "data")
            );
        }

        return $this->template->render(
            "setup/update/update_info",
            compact("serverModules", "filesPrivileges", "filesDelete")
        );
    }
}
