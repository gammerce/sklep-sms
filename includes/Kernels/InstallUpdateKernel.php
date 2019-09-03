<?php
namespace App\Kernels;

use App\Middlewares\RequireInstalledAndNotUpdated;
use Install\DatabaseMigration;
use Install\InstallManager;
use Install\Update;
use Install\UpdateInfo;
use Symfony\Component\HttpFoundation\Request;

class InstallUpdateKernel extends Kernel
{
    protected $middlewares = [RequireInstalledAndNotUpdated::class];

    public function run(Request $request)
    {
        /** @var InstallManager $installManager */
        $installManager = $this->app->make(InstallManager::class);

        /** @var DatabaseMigration $migrator */
        $migrator = $this->app->make(DatabaseMigration::class);

        /** @var UpdateInfo $updateInfo */
        $updateInfo = $this->app->make(UpdateInfo::class);

        /** @var Update $update */
        $update = $this->app->make(Update::class);

        list($modules, $filesPriv, $filesDel) = $update->get();

        $everythingOk = true;
        $updateBody = $updateInfo->updateInfo($everythingOk, $filesPriv, $filesDel, $modules);

        // Nie wszystko jest git
        if (!$everythingOk) {
            json_output(
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

        json_output('ok', "Instalacja przebiegła pomyślnie.", true);
    }
}
