<?php
namespace App\Controllers\Api;

use App\Install\DatabaseMigration;
use App\Install\RequirementsStore;
use App\Install\InstallManager;
use App\Install\UpdateInfo;
use App\Responses\ApiResponse;
use App\Responses\HtmlResponse;
use Symfony\Component\HttpFoundation\Request;

class UpdateController
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
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/install.log'
            );
        }

        if ($installManager->isInProgress()) {
            return new HtmlResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona."
            );
        }

        $modules = [];
        $filesWithWritePermission = $requirementsStore->getFilesWithWritePermission();
        $filesToDelete = $requirementsStore->getFilesToDelete();

        $everythingOk = true;
        $updateBody = $updateInfo->updateInfo(
            $everythingOk,
            $filesWithWritePermission,
            $filesToDelete,
            $modules
        );

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
