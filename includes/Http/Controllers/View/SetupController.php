<?php
namespace App\Http\Controllers\View;

use App\Http\Responses\HtmlResponse;
use App\Install\OldShop;
use App\Install\RequirementStore;
use App\Install\ShopState;
use App\Install\UpdateInfo;
use App\Support\FileSystemContract;
use App\Support\BasePath;
use App\Theme\Template;
use Symfony\Component\HttpFoundation\Response;

class SetupController
{
    private Template $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function get(
        OldShop $oldShop,
        ShopState $shopState,
        UpdateInfo $updateInfo,
        RequirementStore $requirementStore,
        FileSystemContract $fileSystem,
        BasePath $path
    ) {
        if ($oldShop->hasConfigFile()) {
            return new HtmlResponse($this->template->render("setup/missing_env"));
        }

        if (!$shopState->isInstalled()) {
            return $this->install($requirementStore, $path, $fileSystem);
        }

        if (!$shopState->isUpToDate()) {
            return $this->update($updateInfo, $requirementStore);
        }

        return new Response("Sklep nie wymaga aktualizacji.");
    }

    private function install(
        RequirementStore $requirementStore,
        BasePath $path,
        FileSystemContract $fileSystem
    ) {
        $modules = $requirementStore->getModules();
        $filesWithWritePermission = $requirementStore->getFilesWithWritePermission();

        $filesPrivileges = "";
        foreach ($filesWithWritePermission as $file) {
            if ($file == "") {
                continue;
            }

            if ($fileSystem->isWritable($path->to($file))) {
                $privilege = "ok";
            } else {
                $privilege = "bad";
            }

            $filesPrivileges .= $this->template->render(
                "setup/install/file_privileges",
                compact("file", "privilege")
            );
        }

        $serverModules = "";
        foreach ($modules as $module) {
            $status = $module["value"] ? "ok" : "bad";
            $title = $module["text"];

            $serverModules .= $this->template->render(
                "setup/install/module",
                compact("title", "status")
            );
        }

        $notifyHttpServer = $this->generateHttpServerNotification();

        $output = $this->template->render(
            "setup/install/index",
            compact("notifyHttpServer", "filesPrivileges", "serverModules")
        );

        return new Response($output);
    }

    private function update(UpdateInfo $updateInfo, RequirementStore $requirementStore)
    {
        $modules = [];
        $filesWithWritePermission = $requirementStore->getFilesWithWritePermission();
        $filesToDelete = $requirementStore->getFilesToDelete();

        $everythingOk = true;
        // Pobieramy informacje o plikach ktore sa git i te ktore sa be
        $filesModulesStatus = $updateInfo->updateInfo(
            $everythingOk,
            $filesWithWritePermission,
            $filesToDelete,
            $modules
        );
        $class = $everythingOk ? "success" : "danger";

        $notifyHttpServer = $this->generateHttpServerNotification();

        $output = $this->template->render(
            "setup/update/index",
            compact("notifyHttpServer", "filesModulesStatus", "class")
        );

        return new Response($output);
    }

    protected function generateHttpServerNotification()
    {
        if (str_contains(strtolower(array_get($_SERVER, "SERVER_SOFTWARE", "")), "apache")) {
            return "";
        }

        return $this->template->render("setup/http_server_notification");
    }
}
