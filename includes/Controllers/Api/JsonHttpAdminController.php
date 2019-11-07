<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Heart;
use App\Repositories\PriceListRepository;
use App\Repositories\ServerRepository;
use App\Responses\ApiResponse;
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

        if ($action == "antispam_question_add" || $action == "antispam_question_edit") {
            if (!get_privileges("manage_antispam_questions")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Pytanie
            if (!$_POST['question']) {
                $warnings['question'][] = $lang->translate('field_no_empty');
            }

            // Odpowiedzi
            if (!$_POST['answers']) {
                $warnings['answers'][] = $lang->translate('field_no_empty');
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            if ($action == "antispam_question_add") {
                $db->query(
                    $db->prepare(
                        "INSERT INTO `" .
                            TABLE_PREFIX .
                            "antispam_questions` ( question, answers ) " .
                            "VALUES ('%s','%s')",
                        [$_POST['question'], $_POST['answers']]
                    )
                );

                return new ApiResponse('ok', $lang->translate('antispam_add'), 1);
            }

            if ($action == "antispam_question_edit") {
                $db->query(
                    $db->prepare(
                        "UPDATE `" .
                            TABLE_PREFIX .
                            "antispam_questions` " .
                            "SET `question` = '%s', `answers` = '%s' " .
                            "WHERE `id` = '%d'",
                        [$_POST['question'], $_POST['answers'], $_POST['id']]
                    )
                );

                if ($db->affectedRows()) {
                    log_info(
                        $langShop->sprintf(
                            $langShop->translate('question_edit'),
                            $user->getUsername(),
                            $user->getUid(),
                            $_POST['id']
                        )
                    );
                    return new ApiResponse('ok', $lang->translate('antispam_edit'), 1);
                }

                return new ApiResponse("not_edited", $lang->translate('antispam_no_edit'), 0);
            }

            throw new UnexpectedValueException();
        }

        if ($action == "service_add" || $action == "service_edit") {
            if (!get_privileges("manage_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // ID
            if (!strlen($_POST['id'])) {
                // Nie podano id usługi
                $warnings['id'][] = $lang->translate('no_service_id');
            } else {
                if ($action == "service_add") {
                    if (strlen($_POST['id']) > 16) {
                        $warnings['id'][] = $lang->translate('long_service_id');
                    }
                }
            }

            if (
                ($action == "service_add" && !isset($warnings['id'])) ||
                ($action == "service_edit" && $_POST['id'] !== $_POST['id2'])
            ) {
                // Sprawdzanie czy usługa o takim ID już istnieje
                if ($heart->getService($_POST['id']) !== null) {
                    $warnings['id'][] = $lang->translate('id_exist');
                }
            }

            // Nazwa
            if (!strlen($_POST['name'])) {
                $warnings['name'][] = $lang->translate('no_service_name');
            }

            // Opis
            if ($warning = check_for_warnings("service_description", $_POST['short_description'])) {
                $warnings['short_description'] = array_merge(
                    (array) $warnings['short_description'],
                    $warning
                );
            }

            // Kolejność
            if (!my_is_integer($_POST['order'])) {
                $warnings['order'][] = $lang->translate('field_integer');
            }

            // Grupy
            foreach ($_POST['groups'] as $group) {
                if (is_null($heart->getGroup($group))) {
                    $warnings['groups[]'][] = $lang->translate('wrong_group');
                    break;
                }
            }

            // Moduł usługi
            if ($action == "service_add") {
                if (($serviceModule = $heart->getServiceModuleS($_POST['module'])) === null) {
                    $warnings['module'][] = $lang->translate('wrong_module');
                }
            } else {
                $serviceModule = $heart->getServiceModule($_POST['id2']);
            }

            // Przed błędami
            if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
                $additionalWarnings = $serviceModule->serviceAdminManagePre($_POST);
                $warnings = array_merge((array) $warnings, (array) $additionalWarnings);
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            // Po błędach wywołujemy metodę modułu
            if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
                $moduleData = $serviceModule->serviceAdminManagePost($_POST);

                // Tworzymy elementy SET zapytania
                if (isset($moduleData['query_set'])) {
                    $set = '';
                    foreach ($moduleData['query_set'] as $element) {
                        if (strlen($set)) {
                            $set .= ", ";
                        }

                        $set .= $db->prepare("`%s` = '{$element['type']}'", [
                            $element['column'],
                            $element['value'],
                        ]);
                    }
                }
            }

            if (isset($set) && strlen($set)) {
                $set = ", " . $set;
            }

            if ($action == "service_add") {
                $db->query(
                    $db->prepare(
                        "INSERT INTO `" .
                            TABLE_PREFIX .
                            "services` " .
                            "SET `id`='%s', `name`='%s', `short_description`='%s', `description`='%s', `tag`='%s', " .
                            "`module`='%s', `groups`='%s', `order` = '%d' " .
                            "{$set}",
                        [
                            $_POST['id'],
                            $_POST['name'],
                            $_POST['short_description'],
                            $_POST['description'],
                            $_POST['tag'],
                            $_POST['module'],
                            implode(";", $_POST['groups']),
                            trim($_POST['order']),
                        ]
                    )
                );

                log_info(
                    $langShop->sprintf(
                        $langShop->translate('service_admin_add'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('service_added'), 1, [
                    'length' => 10000,
                ]);
            }

            if ($action == "service_edit") {
                $db->query(
                    $db->prepare(
                        "UPDATE `" .
                            TABLE_PREFIX .
                            "services` " .
                            "SET `id` = '%s', `name` = '%s', `short_description` = '%s', `description` = '%s', " .
                            "`tag` = '%s', `groups` = '%s', `order` = '%d' " .
                            $set .
                            "WHERE `id` = '%s'",
                        [
                            $_POST['id'],
                            $_POST['name'],
                            $_POST['short_description'],
                            $_POST['description'],
                            $_POST['tag'],
                            implode(";", $_POST['groups']),
                            $_POST['order'],
                            $_POST['id2'],
                        ]
                    )
                );

                if ($db->affectedRows()) {
                    log_info(
                        $langShop->sprintf(
                            $langShop->translate('service_admin_edit'),
                            $user->getUsername(),
                            $user->getUid(),
                            $_POST['id2']
                        )
                    );
                    return new ApiResponse('ok', $lang->translate('service_edit'), 1);
                }
                return new ApiResponse("not_edited", $lang->translate('service_no_edit'), 0);
            }

            throw new UnexpectedValueException();
        }

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
