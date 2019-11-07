<?php
namespace App\Http\Controllers\Api;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Heart;
use App\Repositories\PriceListRepository;
use App\Repositories\ServerRepository;
use App\Http\Responses\ApiResponse;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\TranslationManager;
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
        ServerRepository $serverRepository,
        PriceListRepository $priceListRepository
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

        if ($action == "group_add" || $action == "group_edit") {
            if (!get_privileges("manage_groups")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $set = "";
            $result = $db->query("DESCRIBE " . TABLE_PREFIX . "groups");
            while ($row = $db->fetchArrayAssoc($result)) {
                if (in_array($row['Field'], ["id", "name"])) {
                    continue;
                }

                $set .= $db->prepare(", `%s`='%d'", [$row['Field'], $_POST[$row['Field']]]);
            }

            if ($action == "group_add") {
                $db->query(
                    $db->prepare(
                        "INSERT INTO `" . TABLE_PREFIX . "groups` " . "SET `name` = '%s'{$set}",
                        [$_POST['name']]
                    )
                );

                log_info(
                    $langShop->sprintf(
                        $langShop->translate('group_admin_add'),
                        $user->getUsername(),
                        $user->getUid(),
                        $db->lastId()
                    )
                );
                // Zwróć info o prawidłowym zakończeniu dodawania
                return new ApiResponse('ok', $lang->translate('group_add'), 1);
            }

            if ($action == "group_edit") {
                $db->query(
                    $db->prepare(
                        "UPDATE `" .
                            TABLE_PREFIX .
                            "groups` " .
                            "SET `name` = '%s'{$set} " .
                            "WHERE `id` = '%d'",
                        [$_POST['name'], $_POST['id']]
                    )
                );

                if ($db->affectedRows()) {
                    log_info(
                        $langShop->sprintf(
                            $langShop->translate('group_admin_edit'),
                            $user->getUsername(),
                            $user->getUid(),
                            $_POST['id']
                        )
                    );
                    return new ApiResponse('ok', $lang->translate('group_edit'), 1);
                }

                return new ApiResponse("not_edited", $lang->translate('group_no_edit'), 0);
            }

            throw new UnexpectedValueException();
        }

        if ($action == "price_add" || $action == "price_edit") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Usługa
            if (is_null($heart->getService($_POST['service']))) {
                $warnings['service'][] = $lang->translate('no_such_service');
            }

            // Serwer
            if ($_POST['server'] != -1 && $heart->getServer($_POST['server']) === null) {
                $warnings['server'][] = $lang->translate('no_such_server');
            }

            // Taryfa
            if ($heart->getTariff($_POST['tariff']) === null) {
                $warnings['tariff'][] = $lang->translate('no_such_tariff');
            }

            // Ilość
            if ($warning = check_for_warnings("number", $_POST['amount'])) {
                $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            if ($action == "price_add") {
                $priceListRepository->create(
                    $_POST['service'],
                    $_POST['tariff'],
                    $_POST['amount'],
                    $_POST['server']
                );

                log_info(
                    "Admin {$user->getUsername()}({$user->getUid()}) dodał cenę. ID: " .
                        $db->lastId()
                );

                return new ApiResponse('ok', $lang->translate('price_add'), 1);
            }

            if ($action == "price_edit") {
                $db->query(
                    $db->prepare(
                        "UPDATE `" .
                            TABLE_PREFIX .
                            "pricelist` " .
                            "SET `service` = '%s', `tariff` = '%d', `amount` = '%d', `server` = '%d' " .
                            "WHERE `id` = '%d'",
                        [
                            $_POST['service'],
                            $_POST['tariff'],
                            $_POST['amount'],
                            $_POST['server'],
                            $_POST['id'],
                        ]
                    )
                );

                if ($db->affectedRows()) {
                    log_info(
                        $langShop->sprintf(
                            $langShop->translate('price_admin_edit'),
                            $user->getUsername(),
                            $user->getUid(),
                            $_POST['id']
                        )
                    );
                    return new ApiResponse('ok', $lang->translate('price_edit'), 1);
                }

                return new ApiResponse("not_edited", $lang->translate('price_no_edit'), 0);
            }

            throw new UnexpectedValueException();
        }

        return new ApiResponse("script_error", "An error occured: no action.");
    }
}
