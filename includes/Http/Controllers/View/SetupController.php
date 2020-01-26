<?php
namespace App\Http\Controllers\View;

use App\Http\Responses\HtmlResponse;
use App\Http\Responses\PlainResponse;
use App\Install\OldShop;
use App\Install\RequirementsStore;
use App\Install\SetupManager;
use App\Install\ShopState;
use App\Install\UpdateInfo;
use App\Support\FileSystemContract;
use App\Support\Path;
use App\Support\Template;
use Symfony\Component\HttpFoundation\Response;

class SetupController
{
    /** @var Template */
    private $template;

    public function __construct(Template $template)
    {
        $this->template = $template;
    }

    public function get(
        OldShop $oldShop,
        ShopState $shopState,
        UpdateInfo $updateInfo,
        RequirementsStore $requirementsStore,
        SetupManager $setupManager,
        FileSystemContract $fileSystem,
        Path $path
    ) {
        if ($oldShop->hasConfigFile()) {
            return new HtmlResponse($this->template->render('setup/missing_env'));
        }

        if ($setupManager->hasFailed()) {
            return new PlainResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/errors.log'
            );
        }

        if ($setupManager->isInProgress()) {
            return new PlainResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona. Usuń plik data/setup_progress, aby przeprowadzić ją ponownie."
            );
        }

        if (!$shopState->isInstalled()) {
            return $this->install($requirementsStore, $path, $fileSystem);
        }

        if (!$shopState->isUpToDate()) {
            return $this->update($updateInfo, $requirementsStore);
        }

        return new Response("Sklep nie wymaga aktualizacji.");
    }

    private function install(
        RequirementsStore $requirementsStore,
        Path $path,
        FileSystemContract $fileSystem
    ) {
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

            if ($fileSystem->isWritable($path->to($file))) {
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

    private function update(UpdateInfo $updateInfo, RequirementsStore $requirementsStore)
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
