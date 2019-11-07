<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Repositories\ServerRepository;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\System\Auth;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class JsonHttpAdminController
{
    public function action(
        Request $request,
        Database $db,
        Heart $heart,
        Auth $auth,
        TranslationManager $translationManager,
        ServerRepository $serverRepository
    ) {
        $langShop = $translationManager->shop();
        $lang = $translationManager->user();
        $user = $auth->user();

        $action = $request->request->get("action");

        $warnings = [];

        if ($action == "server_add" || $action == "server_edit") {
            if (!get_privileges("manage_servers")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Nazwa
            if (!$_POST['name']) {
                // Nie podano nazwy serwera
                $warnings['name'][] = $lang->translate('field_no_empty');
            }

            // IP
            if (!$_POST['ip']) {
                // Nie podano nazwy serwera
                $warnings['ip'][] = $lang->translate('field_no_empty');
            }
            $_POST['ip'] = trim($_POST['ip']);

            // Port
            if (!$_POST['port']) {
                // Nie podano nazwy serwera
                $warnings['port'][] = $lang->translate('field_no_empty');
            }
            $_POST['port'] = trim($_POST['port']);

            // Serwis płatności SMS
            if ($_POST['sms_service']) {
                $result = $db->query(
                    $db->prepare(
                        "SELECT id " .
                            "FROM `" .
                            TABLE_PREFIX .
                            "transaction_services` " .
                            "WHERE `id` = '%s' AND sms = '1'",
                        [$_POST['sms_service']]
                    )
                );
                if (!$db->numRows($result)) {
                    $warnings['sms_service'][] = $lang->translate('no_sms_service');
                }
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            if ($action == "server_add") {
                $server = $serverRepository->create(
                    $_POST['name'],
                    $_POST['ip'],
                    $_POST['port'],
                    $_POST['sms_service']
                );
                $serverId = $server->getId();
            } elseif ($action == "server_edit") {
                $db->query(
                    $db->prepare(
                        "UPDATE `" .
                            TABLE_PREFIX .
                            "servers` " .
                            "SET `name` = '%s', `ip` = '%s', `port` = '%s', `sms_service` = '%s' " .
                            "WHERE `id` = '%s'",
                        [
                            $_POST['name'],
                            $_POST['ip'],
                            $_POST['port'],
                            $_POST['sms_service'],
                            $_POST['id'],
                        ]
                    )
                );

                $serverId = $_POST['id'];
            }

            // Aktualizujemy powiazania serwerow z uslugami
            if ($serverId) {
                $serversServices = [];
                foreach ($heart->getServices() as $service) {
                    // Dana usługa nie może być kupiona na serwerze
                    if (
                        !is_null($serviceModule = $heart->getServiceModule($service['id'])) &&
                        !($serviceModule instanceof IServiceAvailableOnServers)
                    ) {
                        continue;
                    }

                    $serversServices[] = [
                        'service' => $service['id'],
                        'server' => $serverId,
                        'status' => (bool) $_POST[$service['id']],
                    ];
                }

                update_servers_services($serversServices);
            }

            if ($action == "server_add") {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('server_admin_add'),
                        $user->getUsername(),
                        $user->getUid(),
                        $serverId
                    )
                );
                return new ApiResponse('ok', $lang->translate('server_added'), 1);
            }

            if ($action == "server_edit") {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('server_admin_edit'),
                        $user->getUsername(),
                        $user->getUid(),
                        $serverId
                    )
                );
                return new ApiResponse('ok', $lang->translate('server_edit'), 1);
            }

            throw new UnexpectedValueException();
        }

        return new ApiResponse("script_error", "An error occured: no action.");
    }
}
