<?php
namespace App\Http\Controllers\Api\Setup;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Install\DatabaseMigration;
use App\Install\RequirementsStore;
use App\Install\SetupManager;
use App\Install\UpdateInfo;
use Exception;

class UpdateController
{
    public function post(
        SetupManager $setupManager,
        DatabaseMigration $migrator,
        UpdateInfo $updateInfo,
        RequirementsStore $requirementsStore
    ) {
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

        try {
            $setupManager->start();
            $migrator->update();
        } catch (Exception $e) {
            $setupManager->markAsFailed();
            throw $e;
        } finally {
            $setupManager->finish();
        }

        return new SuccessApiResponse("Instalacja przebiegła pomyślnie.");
    }
}
