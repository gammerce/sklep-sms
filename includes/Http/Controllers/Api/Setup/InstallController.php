<?php
namespace App\Http\Controllers\Api\Setup;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Install\DatabaseMigration;
use App\Install\EnvCreator;
use App\Install\RequirementsStore;
use App\Install\SetupManager;
use App\Support\Database;
use App\Support\FileSystemContract;
use App\Support\Path;
use App\System\Application;
use Exception;
use PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InstallController
{
    public function post(
        Request $request,
        RequirementsStore $requirementsStore,
        EnvCreator $envCreator,
        SetupManager $setupManager,
        Path $path,
        FileSystemContract $fileSystem,
        Application $app
    ) {
        $validator = new Validator($request->request->all(), [
            "admin_password" => [new RequiredRule()],
            "admin_username" => [new RequiredRule()],
            "license_token" => [new RequiredRule()],
            "db_host" => [],
            "db_port" => [],
            "db_user" => [],
            "db_password" => [],
            "db_db" => [],
        ]);

        $warnings = $validator->validate();

        foreach ($requirementsStore->getFilesWithWritePermission() as $file) {
            if (strlen($file) && !$fileSystem->isWritable($path->to($file))) {
                $warnings->add(
                    "general",
                    "Ścieżka <b>" . htmlspecialchars($file) . "</b> nie posiada praw do zapisu."
                );
            }
        }

        foreach ($requirementsStore->getModules() as $module) {
            if (!$module["value"] && $module["must-be"]) {
                $warnings->add(
                    "general",
                    "Wymaganie: <b>{$module["text"]}</b> nie jest spełnione."
                );
            }
        }

        if ($warnings->isPopulated()) {
            throw new ValidationException($warnings);
        }

        $validated = $validator->validated();

        $dbHost = $validated["db_host"];
        $dbPort = $validated["db_port"];
        $dbUser = $validated["db_user"];
        $dbPassword = $validated["db_password"];
        $dbDb = $validated["db_db"];

        try {
            $db = new Database($dbHost, $dbPort, $dbUser, $dbPassword, $dbDb);
            $db->connect();
            $app->instance(Database::class, $db);
        } catch (PDOException $e) {
            return new Response($e->getMessage());
        }

        /** @var DatabaseMigration $migrator */
        $migrator = $app->make(DatabaseMigration::class);

        try {
            $setupManager->start();
            $migrator->setup(
                $validated["license_token"],
                $validated["admin_username"],
                $validated["admin_password"],
                get_ip($request)
            );
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
