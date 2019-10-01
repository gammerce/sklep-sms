<?php
namespace App\Controllers;

use App\Install\DatabaseMigration;
use App\Install\RequirementsStore;
use App\Install\InstallManager;
use App\Install\UpdateInfo;
use App\Responses\ApiResponse;
use App\Responses\HtmlResponse;
use Symfony\Component\HttpFoundation\Request;

class InstallUpdateController
{
    public function post(
        Request $request,
        InstallManager $installManager,
        DatabaseMigration $migrator,
        UpdateInfo $updateInfo,
        RequirementsStore $requirementsStore
    ) {
        if ($installManager->hasFailed()) {
            return new HtmlResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku errors/install.log'
            );
        }

        if ($installManager->isInProgress()) {
            return new HtmlResponse("Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona.");
        }

        $modules = [];
        $filesPriv = $requirementsStore->getFilesWithWritePermission();
        $filesDel = $requirementsStore->getFilesToDelete();

        $everythingOk = true;
        $updateBody = $updateInfo->updateInfo($everythingOk, $filesPriv, $filesDel, $modules);

        // Nie wszystko jest git
        if (!$everythingOk) {
            return new ApiResponse(
                "warnings",
                "Aktualizacja nie mogła zostać przeprowadzona. Nie wszystkie warunki są spełnione.",
                false,
                [
                    'update_info' => $updateBody,
                ]
            );
        }

        // -------------------- INSTALACJA --------------------

        $installManager->start();
        $migrator->update();
        $installManager->finish();

        return new ApiResponse('ok', "Instalacja przebiegła pomyślnie.", true);
    }
}
