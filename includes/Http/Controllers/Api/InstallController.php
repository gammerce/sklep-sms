<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\HtmlResponse;
use App\Install\DatabaseMigration;
use App\Install\EnvCreator;
use App\Install\RequirementsStore;
use App\Install\SetupManager;
use App\System\Application;
use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;
use Exception;
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallController
{
    public function post(
        Request $request,
        RequirementsStore $requirementsStore,
        SetupManager $setupManager,
        Path $path,
        FileSystemContract $fileSystem,
        Application $app
    ) {
        if ($setupManager->hasFailed()) {
            return new HtmlResponse(
                'Wystąpił błąd podczas aktualizacji. Poinformuj o swoim problemie. Nie zapomnij dołączyć pliku data/logs/errors.log'
            );
        }

        if ($setupManager->isInProgress()) {
            return new HtmlResponse(
                "Instalacja/Aktualizacja trwa, lub została błędnie przeprowadzona. Usuń plik data/setup_progress, aby przeprowadzić ją ponownie."
            );
        }

        $modules = $requirementsStore->getModules();
        $filesWithWritePermission = $requirementsStore->getFilesWithWritePermission();

        $dbHost = $request->request->get('db_host');
        $dbPort = $request->request->get('db_port');
        $dbUser = $request->request->get('db_user');
        $dbPassword = $request->request->get('db_password');
        $dbDb = $request->request->get('db_db');
        $licenseToken = $request->request->get('license_token');
        $adminUsername = $request->request->get('admin_username');
        $adminPassword = $request->request->get('admin_password');

        try {
            $db = new Database($dbHost, $dbPort, $dbUser, $dbPassword, $dbDb);
            $db->connect();
            $app->instance(Database::class, $db);
        } catch (PDOException $e) {
            return new Response($e->getMessage());
        }

        /** @var SetupManager $setupManager */
        $setupManager = $app->make(SetupManager::class);

        /** @var DatabaseMigration $migrator */
        $migrator = $app->make(DatabaseMigration::class);

        /** @var EnvCreator $envCreator */
        $envCreator = $app->make(EnvCreator::class);

        $warnings = [];

        if (!strlen($licenseToken)) {
            $warnings['license_token'][] = "Nie podano tokenu licencji.";
        }

        if (!strlen($adminUsername)) {
            $warnings['admin_username'][] = "Nie podano nazwy dla użytkownika admin.";
        }

        if (!strlen($adminPassword)) {
            $warnings['admin_password'][] = "Nie podano hasła dla użytkownika admin.";
        }

        foreach ($filesWithWritePermission as $file) {
            if (strlen($file) && !$fileSystem->isWritable($path->to($file))) {
                $warnings['general'][] =
                    "Ścieżka <b>" . htmlspecialchars($file) . "</b> nie posiada praw do zapisu.";
            }
        }

        // Sprawdzamy ustawienia modułuów
        foreach ($modules as $module) {
            if (!$module['value'] && $module['must-be']) {
                $warnings['general'][] = "Wymaganie: <b>{$module['text']}</b> nie jest spełnione.";
            }
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        try {
            $setupManager->start();
            $migrator->setup($licenseToken, $adminUsername, $adminPassword);
            $envCreator->create($dbHost, $dbPort, $dbDb, $dbUser, $dbPassword);
        } catch (Exception $e) {
            $setupManager->markAsFailed();
            throw $e;
        } finally {
            $setupManager->finish();
        }

        return new ApiResponse("ok", "Instalacja przebiegła pomyślnie.", true);
    }
}
