<?php
namespace App\Kernels;

use App\Database;
use App\Exceptions\SqlQueryException;
use App\Middlewares\RequireNotInstalled;
use App\TranslationManager;
use Install\DatabaseMigration;
use Install\EnvCreator;
use Install\Full;
use Install\InstallManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallFullKernel extends Kernel
{
    protected $middlewares = [
        RequireNotInstalled::class,
    ];

    public function run(Request $request)
    {
        /** @var Full $full */
        $full = $this->app->make(Full::class);

        list($modules, $files_priv) = $full->get();

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        try {
            $db = new Database($_POST['db_host'], $_POST['db_port'], $_POST['db_user'], $_POST['db_password'], $_POST['db_db']);
            $db->query("SET NAMES utf8");
            $this->app->instance(Database::class, $db);
        } catch (SqlQueryException $e) {
            return new Response($lang->translate('mysqli_' . $e->getMessage()) . "\n\n" . $e->getError());
        }

        /** @var InstallManager $installManager */
        $installManager = $this->app->make(InstallManager::class);

        /** @var DatabaseMigration $migrator */
        $migrator = $this->app->make(DatabaseMigration::class);

        /** @var EnvCreator $envCreator */
        $envCreator = $this->app->make(EnvCreator::class);

        $warnings = [];

        // Licencja ID
        if (!strlen($_POST['license_id'])) {
            $warnings['license_id'][] = "Nie podano ID licencji.";
        }

        // Licencja hasło
        if (!strlen($_POST['license_password'])) {
            $warnings['license_password'][] = "Nie podano hasła licencji.";
        }

        // Admin nick
        if (!strlen($_POST['admin_username'])) {
            $warnings['admin_username'][] = "Nie podano nazwy dla użytkownika admin.";
        }

        // Admin hasło
        if (!strlen($_POST['admin_password'])) {
            $warnings['admin_password'][] = "Nie podano hasła dla użytkownika admin.";
        }

        foreach ($files_priv as $file) {
            if (!strlen($file)) {
                continue;
            }

            if (!is_writable($this->app->path($file))) {
                $warnings['general'][] = "Ścieżka <b>" . htmlspecialchars($file) . "</b> nie posiada praw do zapisu.";
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
            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            foreach ($warnings as $brick => $warning) {
                if (empty($warning)) {
                    continue;
                }

                if ($brick != "general") {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                }

                $return_data['warnings'][$brick] = $warning;
            }

            json_output("warnings", $lang->translate('form_wrong_filled'), false, $return_data);
        }

        $installManager->start();

        $migrator->install($_POST['license_token'], $_POST['admin_username'], $_POST['admin_password']);

        $envCreator->create($_POST['db_host'], $_POST['db_port'], $_POST['db_db'], $_POST['db_user'], $_POST['db_password']);

        $installManager->finish();

        json_output("ok", "Instalacja przebiegła pomyślnie.", true);
    }
}
