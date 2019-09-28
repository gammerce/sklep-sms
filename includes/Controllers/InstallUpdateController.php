<?php
namespace App\Controllers;

use App\Responses\ApiResponse;
use App\Install\DatabaseMigration;
use App\Install\InstallManager;
use App\Install\Update;
use App\Install\UpdateInfo;
use Symfony\Component\HttpFoundation\Request;

class InstallUpdateController
{
    public function post(Request $request, InstallManager $installManager, DatabaseMigration $migrator, UpdateInfo $updateInfo, Update $update)
    {
        list($modules, $filesPriv, $filesDel) = $update->get();

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
