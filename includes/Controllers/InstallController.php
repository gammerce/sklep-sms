<?php
namespace App\Controllers;

use App\Application;
use App\ShopState;
use App\Template;
use App\Install\Full;
use App\Install\OldShop;
use App\Install\Update;
use App\Install\UpdateInfo;
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
        Full $full,
        UpdateInfo $updateInfo,
        Update $update
    ) {
        $oldShop->checkForConfigFile();

        if (!ShopState::isInstalled()) {
            return $this->full($full);
        }

        if (!$shopState->isUpToDate()) {
            return $this->update($updateInfo, $update);
        }

        return new Response(
            "Sklep nie wymaga aktualizacji. Przejdź na stronę sklepu usuwająć z paska adresu /install"
        );
    }

    protected function full(Full $full)
    {
        list($modules, $filesPriv) = $full->get();

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

            $serverModules .= $this->template->render('install/full/module', compact('title', 'status'));
        }

        $notifyHttpServer = $this->generateHttpServerNotification();

        // Pobranie ostatecznego szablonu
        $output = $this->template->render(
            'install/full/index',
            compact('notifyHttpServer', 'filesPrivileges', 'serverModules')
        );

        return new Response($output);
    }

    protected function update(UpdateInfo $updateInfo, Update $update)
    {
        list($modules, $filesPriv, $filesDel) = $update->get();

        $everythingOk = true;
        // Pobieramy informacje o plikach ktore sa git i te ktore sa be
        $filesModulesStatus = $updateInfo->updateInfo(
            $everythingOk,
            $filesPriv,
            $filesDel,
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
