<?php
namespace App\Controllers;

use App\Application;
use App\Database;
use App\Exceptions\SqlQueryException;
use App\Install\DatabaseMigration;
use App\Install\EnvCreator;
use App\Install\InstallManager;
use App\Install\RequirementsStore;
use App\Responses\ApiResponse;
use App\Responses\HtmlResponse;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallFullController
{
    public function post(
        Request $request,
        RequirementsStore $requirementsStore,
        TranslationManager $translationManager,
        InstallManager $installManager,
        Application $app
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

        $modules = $requirementsStore->getModules();
        $filesPriv = $requirementsStore->getFilesWithWritePermission();
        $lang = $translationManager->user();

        try {
            $db = new Database(
                $_POST['db_host'],
                $_POST['db_port'],
                $_POST['db_user'],
                $_POST['db_password'],
                $_POST['db_db']
            );
            $db->query("SET NAMES utf8");
            $app->instance(Database::class, $db);
        } catch (SqlQueryException $e) {
            return new Response(
                $lang->translate('mysqli_' . $e->getMessage()) . "\n\n" . $e->getError()
            );
        }

        /** @var InstallManager $installManager */
        $installManager = $app->make(InstallManager::class);

        /** @var DatabaseMigration $migrator */
        $migrator = $app->make(DatabaseMigration::class);

        /** @var EnvCreator $envCreator */
        $envCreator = $app->make(EnvCreator::class);

        $warnings = [];

        // Licencja ID
        if (!strlen($_POST['license_token'])) {
            $warnings['license_token'][] = "Nie podano tokenu licencji.";
        }

        // Admin nick
        if (!strlen($_POST['admin_username'])) {
            $warnings['admin_username'][] = "Nie podano nazwy dla użytkownika admin.";
        }

        // Admin hasło
        if (!strlen($_POST['admin_password'])) {
            $warnings['admin_password'][] = "Nie podano hasła dla użytkownika admin.";
        }

        foreach ($filesPriv as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (!is_writable($app->path($file))) {
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

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            $returnData['warnings'] = format_warnings($warnings);
            return new ApiResponse(
                "warnings",
                $lang->translate('form_wrong_filled'),
                false,
                $returnData
            );
        }

        $installManager->start();

        $migrator->install(
            $_POST['license_token'],
            $_POST['admin_username'],
            $_POST['admin_password']
        );

        $envCreator->create(
            $_POST['db_host'],
            $_POST['db_port'],
            $_POST['db_db'],
            $_POST['db_user'],
            $_POST['db_password']
        );

        $installManager->finish();

        return new ApiResponse("ok", "Instalacja przebiegła pomyślnie.", true);
    }
}
