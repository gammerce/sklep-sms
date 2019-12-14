<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\HtmlResponse;
use App\Install\DatabaseMigration;
use App\Install\RequirementsStore;
use App\Install\SetupManager;
use App\Install\UpdateInfo;
use Symfony\Component\HttpFoundation\Request;

class UpdateController
{
    public function post(
        Request $request,
        SetupManager $setupManager,
        DatabaseMigration $migrator,
        UpdateInfo $updateInfo,
        RequirementsStore $requirementsStore
    ) {
        if ($setupManager->hasFailed()) {
            return new HtmlResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/install.log'
            );
        }

        if ($setupManager->isInProgress()) {
            return new HtmlResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona. Usuń plik data/setup_progress, aby przeprowadzić ją ponownie."
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

        $setupManager->start();
        $migrator->update();
        $setupManager->finish();

        return new ApiResponse('ok', "Instalacja przebiegła pomyślnie.", true);
    }
}
