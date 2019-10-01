<?php
namespace App\Controllers;

use App\Application;
use App\Install\RequirementsStore;
use App\Install\Full;
use App\Install\InstallManager;
use App\Install\OldShop;
use App\Install\UpdateInfo;
use App\Responses\HtmlResponse;
use App\ShopState;
use App\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallController
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
        InstallManager $installManager
    ) {
        $oldShop->checkForConfigFile();

        if ($installManager->hasFailed()) {
            return new HtmlResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/install.log'
            );
        }

        if ($installManager->isInProgress()) {
            return new HtmlResponse("Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona.");
        }

        if (!ShopState::isInstalled()) {
            return $this->full($requirementsStore);
        }

        if (!$shopState->isUpToDate()) {
            return $this->update($updateInfo, $requirementsStore);
        }

        return new Response(
            "Sklep nie wymaga aktualizacji. Przejdź na stronę sklepu usuwająć z paska adresu /install"
        );
    }

    protected function full(RequirementsStore $requirementsStore)
    {
        $modules = $requirementsStore->getModules();
        $filesPriv = $requirementsStore->getFilesWithWritePermission();

        // #########################################
        // ##########    Wyświetl dane    ##########
        // #########################################

        $filesPrivileges = '';
        foreach ($filesPriv as $file) {
            if ($file == "") {
                continue;
            }

            if (is_writable($this->app->path($file))) {
                $privilege = "ok";
            } else {
                $privilege = "bad";
            }

            $filesPrivileges .= $this->template->render(
                'install/full/file_privileges',
                compact('file', 'privilege')
            );
        }

        $serverModules = '';
        foreach ($modules as $module) {
            $status = $module['value'] ? "ok" : "bad";
            $title = $module['text'];

            $serverModules .= $this->template->render(
                'install/full/module',
                compact('title', 'status')
            );
        }

        $notifyHttpServer = $this->generateHttpServerNotification();

        // Pobranie ostatecznego szablonu
        $output = $this->template->render(
            'install/full/index',
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
            'install/update/index',
            compact('notifyHttpServer', 'filesModulesStatus', 'class')
        );

        return new Response($output);
    }

    protected function generateHttpServerNotification()
    {
        if (str_contains(strtolower($_SERVER["SERVER_SOFTWARE"]), 'apache')) {
            return '';
        }

        return $this->template->render('install/http_server_notification');
    }
}
