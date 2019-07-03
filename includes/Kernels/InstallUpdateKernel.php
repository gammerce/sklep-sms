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

        list($modules, $files_priv, $files_del) = $update->get();

        $everything_ok = true;
        $update_info = $updateInfo->updateInfo($everything_ok, $files_priv, $files_del, $modules);

        // Nie wszystko jest git
        if (!$everything_ok) {
            json_output(
                "warnings",
                "Aktualizacja nie mogła zostać przeprowadzona. Nie wszystkie warunki są spełnione.",
                false,
                [
                    'update_info' => $update_info
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
