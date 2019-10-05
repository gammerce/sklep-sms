<?php
namespace App\Controllers\View;

use App\Application;
use App\Install\RequirementsStore;
use App\Install\SetupManager;
use App\Install\OldShop;
use App\Install\UpdateInfo;
use App\Responses\HtmlResponse;
use App\ShopState;
use App\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SetupController
{
    /** @var Application */
    private $app;

    /** @var Template */
    private $template;

    public function __construct(Application $app, Template $template)
    {
        $this->template = $template;
        $this->app = $app;
    }

    public function get(
        Request $request,
        OldShop $oldShop,
        ShopState $shopState,
        UpdateInfo $updateInfo,
        RequirementsStore $requirementsStore,
        SetupManager $setupManager
    ) {
        $oldShop->checkForConfigFile();

        if ($setupManager->hasFailed()) {
            return new HtmlResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/install.log'
            );
        }

        if ($setupManager->isInProgress()) {
            return new HtmlResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona."
            );
        }

        if (!ShopState::isInstalled()) {
            return $this->install($requirementsStore);
        }

        if (!$shopState->isUpToDate()) {
            return $this->update($updateInfo, $requirementsStore);
        }

        return new Response("Sklep nie wymaga aktualizacji.");
    }

    protected function install(RequirementsStore $requirementsStore)
    {
        $modules = $requirementsStore->getModules();
        $filesWithWritePermission = $requirementsStore->getFilesWithWritePermission();

        // #########################################
        // ##########    Wyświetl dane    ##########
        // #########################################

        $filesPrivileges = '';
        foreach ($filesWithWritePermission as $file) {
            if ($file == "") {
                continue;
            }

            if (is_writable($this->app->path($file))) {
                $privilege = "ok";
            } else {
                $privilege = "bad";
            }

            $filesPrivileges .= $this->template->render(
                'setup/install/file_privileges',
                compact('file', 'privilege')
            );
        }

        $serverModules = '';
        foreach ($modules as $module) {
            $status = $module['value'] ? "ok" : "bad";
            $title = $module['text'];

            $serverModules .= $this->template->render(
                'setup/install/module',
                compact('title', 'status')
            );
        }

        $notifyHttpServer = $this->generateHttpServerNotification();

        // Pobranie ostatecznego szablonu
        $output = $this->template->render(
            'setup/install/index',
            compact('notifyHttpServer', 'filesPrivileges', 'serverModules')
        );

        return new Response($output);
    }

    protected function update(UpdateInfo $updateInfo, RequirementsStore $requirementsStore)
    {
        $modules = [];
        $filesWithWritePermission = $requirementsStore->getFilesWithWritePermission();
        $filesToDelete = $requirementsStore->getFilesToDelete();

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

        // Pobranie ostatecznego szablonu
        $output = $this->template->render(
            'setup/update/index',
            compact('notifyHttpServer', 'filesModulesStatus', 'class')
        );

        return new Response($output);
    }

    protected function generateHttpServerNotification()
    {
        if (str_contains(strtolower($_SERVER["SERVER_SOFTWARE"]), 'apache')) {
            return '';
        }

        return $this->template->render('setup/http_server_notification');
    }
}
