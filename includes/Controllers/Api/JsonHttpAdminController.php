<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Database;
use App\Exceptions\SqlQueryException;
use App\Exceptions\ValidationException;
use App\Heart;
use App\Path;
use App\Repositories\PriceListRepository;
use App\Repositories\ServerRepository;
use App\Responses\ApiResponse;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
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
        Path $path,
        TranslationManager $translationManager,
        ServerRepository $serverRepository,
        PriceListRepository $priceListRepository
    ) {
        $langShop = $translationManager->shop();
        $lang = $translationManager->user();
        $user = $auth->user();

        // Pobranie akcji
        $action = $request->request->get("action");

        $warnings = [];

        if ($action == "user_service_add") {
            if (!get_privileges("manage_user_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Brak usługi
            if (!strlen($_POST['service'])) {
                return new ApiResponse("no_service", $lang->translate('no_service_chosen'), 0);
            }

            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) === null ||
                !($serviceModule instanceof IServiceUserServiceAdminAdd)
            ) {
                return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
            }

            $returnData = $serviceModule->userServiceAdminAdd($_POST);

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if ($returnData['status'] == "warnings") {
                $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
            }

            return new ApiResponse(
                $returnData['status'],
                $returnData['text'],
                $returnData['positive'],
                $returnData['data']
            );
        }

        if ($action == "user_service_edit") {
            if (!get_privileges("manage_user_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Brak usługi
            if (!strlen($_POST['service'])) {
                return new ApiResponse("no_service", "Nie wybrano usługi.", 0);
            }

            if (is_null($serviceModule = $heart->getServiceModule($_POST['service']))) {
                return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
            }

            $userService = get_users_services($_POST['id']);

            // Brak takiej usługi w bazie
            if (empty($userService)) {
                return new ApiResponse("no_service", $lang->translate('no_service'), 0);
            }

            // Wykonujemy metode edycji usługi użytkownika przez admina na odpowiednim module
            $returnData = $serviceModule->userServiceAdminEdit($_POST, $userService);

            if ($returnData === false) {
                return new ApiResponse("missing_method", $lang->translate('no_edit_method'), 0);
            }

            if ($returnData['status'] == "warnings") {
                $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
            }

            return new ApiResponse(
                $returnData['status'],
                $returnData['text'],
                $returnData['positive'],
                $returnData['data']
            );
        }

        if ($action == "user_service_delete") {
            if (!get_privileges("manage_user_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $userService = get_users_services($_POST['id']);

            // Brak takiej usługi
            if (empty($userService)) {
                return new ApiResponse("no_service", $lang->translate('no_service'), 0);
            }

            // Wywolujemy akcje przy usuwaniu
            if (
                ($serviceModule = $heart->getServiceModule($userService['service'])) !== null &&
                !$serviceModule->userServiceDelete($userService, 'admin')
            ) {
                return new ApiResponse(
                    "user_service_cannot_be_deleted",
                    $lang->translate('user_service_cannot_be_deleted'),
                    0
                );
            }

            // Usunięcie usługi użytkownika
            $db->query(
                $db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "user_service` " . "WHERE `id` = '%d'",
                    [$userService['id']]
                )
            );
            $affected = $db->affectedRows();

            if ($serviceModule !== null) {
                $serviceModule->userServiceDeletePost($userService);
            }

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($affected) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('user_service_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $userService['id']
                    )
                );

                return new ApiResponse('ok', $lang->translate('delete_service'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_service'), 0);
        }

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

                // Zwróć info o prawidłowej lub błędnej edycji
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

        if ($action == "delete_antispam_question") {
            if (!get_privileges("manage_antispam_questions")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "antispam_questions` " . "WHERE `id` = '%d'",
                    [$_POST['id']]
                )
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('question_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_antispamq'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_antispamq'), 0);
        }

        if ($action == "settings_edit") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $smsService = $_POST['sms_service'];
            $transferService = $_POST['transfer_service'];
            $currency = $_POST['currency'];
            $shopName = $_POST['shop_name'];
            $shopUrl = $_POST['shop_url'];
            $senderEmail = $_POST['sender_email'];
            $senderEmailName = $_POST['sender_email_name'];
            $signature = $_POST['signature'];
            $vat = $_POST['vat'];
            $contact = $_POST['contact'];
            $rowLimit = $_POST['row_limit'];
            $licenseToken = $_POST['license_token'];
            $cron = $_POST['cron'];
            $language = escape_filename($_POST['language']);
            $theme = escape_filename($_POST['theme']);
            $dateFormat = $_POST['date_format'];
            $deleteLogs = $_POST['delete_logs'];
            $googleAnalytics = trim($_POST['google_analytics']);
            $gadugadu = $_POST['gadugadu'];

            // Serwis płatności SMS
            if (strlen($smsService)) {
                $result = $db->query(
                    $db->prepare(
                        "SELECT id " .
                            "FROM `" .
                            TABLE_PREFIX .
                            "transaction_services` " .
                            "WHERE `id` = '%s' AND sms = '1'",
                        [$smsService]
                    )
                );
                if (!$db->numRows($result)) {
                    $warnings['sms_service'][] = $lang->translate('no_sms_service');
                }
            }

            // Serwis płatności internetowej
            if (strlen($transferService)) {
                $result = $db->query(
                    $db->prepare(
                        "SELECT id " .
                            "FROM `" .
                            TABLE_PREFIX .
                            "transaction_services` " .
                            "WHERE `id` = '%s' AND transfer = '1'",
                        [$transferService]
                    )
                );
                if (!$db->numRows($result)) {
                    $warnings['transfer_service'][] = $lang->translate('no_net_service');
                }
            }

            // Email dla automatu
            if (strlen($senderEmail) && ($warning = check_for_warnings("email", $senderEmail))) {
                $warnings['sender_email'] = array_merge(
                    (array) $warnings['sender_email'],
                    $warning
                );
            }

            // VAT
            if ($warning = check_for_warnings("number", $vat)) {
                $warnings['vat'] = array_merge((array) $warnings['vat'], $warning);
            }

            // Usuwanie logów
            if ($warning = check_for_warnings("number", $deleteLogs)) {
                $warnings['delete_logs'] = array_merge((array) $warnings['delete_logs'], $warning);
            }

            // Wierszy na stronę
            if ($warning = check_for_warnings("number", $rowLimit)) {
                $warnings['row_limit'] = array_merge((array) $warnings['row_limit'], $warning);
            }

            // Cron
            if (!in_array($cron, ["1", "0"])) {
                $warnings['cron'][] = $lang->translate('only_yes_no');
            }

            // Edytowanie usługi przez użytkownika
            if (!in_array($_POST['user_edit_service'], ["1", "0"])) {
                $warnings['user_edit_service'][] = $lang->translate('only_yes_no');
            }

            // Motyw
            if (!is_dir($path->to("themes/{$theme}")) || $theme[0] == '.') {
                $warnings['theme'][] = $lang->translate('no_theme');
            }

            // Język
            if (!is_dir($path->to("translations/{$language}")) || $language[0] == '.') {
                $warnings['language'][] = $lang->translate('no_language');
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            if ($licenseToken) {
                $setLicenseToken = $db->prepare(
                    "WHEN 'license_password' THEN '%s' WHEN 'license_login' THEN 'license' ",
                    [$licenseToken]
                );
                $keyLicenseToken = ",'license_password', 'license_login'";
            }

            // Edytuj ustawienia
            $db->query(
                $db->prepare(
                    "UPDATE `" .
                        TABLE_PREFIX .
                        "settings` " .
                        "SET value = CASE `key` " .
                        "WHEN 'sms_service' THEN '%s' " .
                        "WHEN 'transfer_service' THEN '%s' " .
                        "WHEN 'currency' THEN '%s' " .
                        "WHEN 'shop_name' THEN '%s' " .
                        "WHEN 'shop_url' THEN '%s' " .
                        "WHEN 'sender_email' THEN '%s' " .
                        "WHEN 'sender_email_name' THEN '%s' " .
                        "WHEN 'signature' THEN '%s' " .
                        "WHEN 'vat' THEN '%.2f' " .
                        "WHEN 'contact' THEN '%s' " .
                        "WHEN 'row_limit' THEN '%s' " .
                        "WHEN 'cron_each_visit' THEN '%d' " .
                        "WHEN 'user_edit_service' THEN '%d' " .
                        "WHEN 'theme' THEN '%s' " .
                        "WHEN 'language' THEN '%s' " .
                        "WHEN 'date_format' THEN '%s' " .
                        "WHEN 'delete_logs' THEN '%d' " .
                        "WHEN 'google_analytics' THEN '%s' " .
                        "WHEN 'gadugadu' THEN '%s' " .
                        $setLicenseToken .
                        "END " .
                        "WHERE `key` IN ( 'sms_service','transfer_service','currency','shop_name','shop_url','sender_email','sender_email_name','signature','vat'," .
                        "'contact','row_limit','cron_each_visit','user_edit_service','theme','language','date_format','delete_logs'," .
                        "'google_analytics','gadugadu'{$keyLicenseToken} )",
                    [
                        $smsService,
                        $transferService,
                        $currency,
                        $shopName,
                        $shopUrl,
                        $senderEmail,
                        $senderEmailName,
                        $signature,
                        $vat,
                        $contact,
                        $rowLimit,
                        $cron,
                        $_POST['user_edit_service'],
                        $theme,
                        $language,
                        $dateFormat,
                        $deleteLogs,
                        $googleAnalytics,
                        $gadugadu,
                    ]
                )
            );

            // Zwróć info o prawidłowej lub błędnej edycji
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('settings_admin_edit'),
                        $user->getUsername(),
                        $user->getUid()
                    )
                );

                return new ApiResponse('ok', $lang->translate('settings_edit'), 1);
            }

            return new ApiResponse("not_edited", $lang->translate('settings_no_edit'), 0);
        }

        if ($action == "transaction_service_edit") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Pobieranie danych
            $result = $db->query(
                $db->prepare(
                    "SELECT data " .
                        "FROM `" .
                        TABLE_PREFIX .
                        "transaction_services` " .
                        "WHERE `id` = '%s'",
                    [$_POST['id']]
                )
            );
            $transactionService = $db->fetchArrayAssoc($result);
            $transactionService['data'] = json_decode($transactionService['data']);
            foreach ($transactionService['data'] as $key => $value) {
                $arr[$key] = $_POST[$key];
            }

            $db->query(
                $db->prepare(
                    "UPDATE `" .
                        TABLE_PREFIX .
                        "transaction_services` " .
                        "SET `data` = '%s' " .
                        "WHERE `id` = '%s'",
                    [json_encode($arr), $_POST['id']]
                )
            );

            // Zwróć info o prawidłowej lub błędnej edycji
            if ($db->affectedRows()) {
                // LOGGING
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('payment_admin_edit'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );

                return new ApiResponse('ok', $lang->translate('payment_edit'), 1);
            }

            return new ApiResponse("not_edited", $lang->translate('payment_no_edit'), 0);
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

                // Zwróć info o prawidłowej lub błędnej edycji
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

        if ($action == "delete_service") {
            if (!get_privileges("manage_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Wywolujemy akcje przy uninstalacji
            $serviceModule = $heart->getServiceModule($_POST['id']);
            if (!is_null($serviceModule)) {
                $serviceModule->serviceDelete($_POST['id']);
            }

            try {
                $db->query(
                    $db->prepare(
                        "DELETE FROM `" . TABLE_PREFIX . "services` " . "WHERE `id` = '%s'",
                        [$_POST['id']]
                    )
                );
            } catch (SqlQueryException $e) {
                if ($e->getErrorno() == 1451) {
                    // Istnieją powiązania
                    return new ApiResponse(
                        "error",
                        $lang->translate('delete_service_er_row_is_referenced_2'),
                        0
                    );
                }

                throw $e;
            }
            $affected = $db->affectedRows();

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($affected) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('service_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_service'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_service'), 0);
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

        if ($action == "delete_server") {
            if (!get_privileges("manage_servers")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            try {
                $db->query(
                    $db->prepare(
                        "DELETE FROM `" . TABLE_PREFIX . "servers` " . "WHERE `id` = '%s'",
                        [$_POST['id']]
                    )
                );
            } catch (SqlQueryException $e) {
                if ($e->getErrorno() == 1451) {
                    // Istnieją powiązania
                    return new ApiResponse(
                        "error",
                        $lang->translate('delete_server_constraint_fails'),
                        0
                    );
                }

                throw $e;
            }

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('server_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_server'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_server'), 0);
        }

        if ($action == "delete_user") {
            if (!get_privileges("manage_users")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "users` " . "WHERE `uid` = '%d'", [
                    $_POST['uid'],
                ])
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('user_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['uid']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_user'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_user'), 0);
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

                // Zwróć info o prawidłowej lub błędnej edycji
                if ($db->affectedRows()) {
                    // LOGGING
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

        if ($action == "delete_group") {
            if (!get_privileges("manage_groups")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "groups` " . "WHERE `id` = '%d'", [
                    $_POST['id'],
                ])
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('group_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_group'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_group'), 0);
        }

        if ($action == "tariff_add") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Taryfa
            if ($warning = check_for_warnings("number", $_POST['id'])) {
                $warnings['id'] = array_merge((array) $warnings['id'], $warning);
            }
            if ($heart->getTariff($_POST['id']) !== null) {
                $warnings['id'][] = $lang->translate('tariff_exist');
            }

            // Prowizja
            if ($warning = check_for_warnings("number", $_POST['provision'])) {
                $warnings['provision'] = array_merge((array) $warnings['provision'], $warning);
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            $db->query(
                $db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "tariffs` " .
                        "SET `id` = '%d', `provision` = '%d'",
                    [$_POST['id'], $_POST['provision'] * 100]
                )
            );

            log_info(
                $langShop->sprintf(
                    $langShop->translate('tariff_admin_add'),
                    $user->getUsername(),
                    $user->getUid(),
                    $db->lastId()
                )
            );

            return new ApiResponse('ok', $lang->translate('tariff_add'), 1);
        }

        if ($action == "tariff_edit") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Prowizja
            if ($warning = check_for_warnings("number", $_POST['provision'])) {
                $warnings['provision'] = array_merge((array) $warnings['provision'], $warning);
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            $db->query(
                $db->prepare(
                    "UPDATE `" .
                        TABLE_PREFIX .
                        "tariffs` " .
                        "SET `provision` = '%d' " .
                        "WHERE `id` = '%d'",
                    [$_POST['provision'] * 100, $_POST['id']]
                )
            );
            $affected = $db->affectedRows();

            // Zwróć info o prawidłowej edycji
            if ($affected || $db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('tariff_admin_edit'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('tariff_edit'), 1);
            }

            return new ApiResponse("not_edited", $lang->translate('tariff_no_edit'), 0);
        }

        if ($action == "delete_tariff") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare(
                    "DELETE FROM `" .
                        TABLE_PREFIX .
                        "tariffs` " .
                        "WHERE `id` = '%d' AND `predefined` = '0'",
                    [$_POST['id']]
                )
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('tariff_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_tariff'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_tariff'), 0);
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

                // Zwróć info o prawidłowym dodaniu
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

                // Zwróć info o prawidłowej lub błędnej edycji
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

        if ($action == "delete_price") {
            if (!get_privileges("manage_settings")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "pricelist` " . "WHERE `id` = '%d'", [
                    $_POST['id'],
                ])
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('price_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_price'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_price'), 0);
        }

        if ($action == "sms_code_add") {
            if (!get_privileges("manage_sms_codes")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Taryfa
            if ($warning = check_for_warnings("number", $_POST['tariff'])) {
                $warnings['tariff'] = array_merge((array) $warnings['tariff'], $warning);
            }

            // Kod SMS
            if ($warning = check_for_warnings("sms_code", $_POST['code'])) {
                $warnings['code'] = array_merge((array) $warnings['code'], $warning);
            }

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            $db->query(
                $db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "sms_codes` (`code`, `tariff`) " .
                        "VALUES( '%s', '%d' )",
                    [$lang->strtoupper($_POST['code']), $_POST['tariff']]
                )
            );

            log_info(
                $langShop->sprintf(
                    $langShop->translate('sms_code_admin_add'),
                    $user->getUsername(),
                    $user->getUid(),
                    $_POST['code'],
                    $_POST['tariff']
                )
            );
            // Zwróć info o prawidłowym dodaniu
            return new ApiResponse('ok', $lang->translate('sms_code_add'), 1);
        }

        if ($action == "delete_sms_code") {
            if (!get_privileges("manage_sms_codes")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "sms_codes` " . "WHERE `id` = '%d'", [
                    $_POST['id'],
                ])
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('sms_code_admin_delete'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('delete_sms_code'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_sms_code'), 0);
        }

        if ($action == "service_code_add") {
            if (!get_privileges("manage_service_codes")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            // Brak usługi
            if (!strlen($_POST['service'])) {
                return new ApiResponse("no_service", $lang->translate('no_service_chosen'), 0);
            }

            if (($serviceModule = $heart->getServiceModule($_POST['service'])) === null) {
                return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
            }

            // Id użytkownika
            if (strlen($_POST['uid']) && ($warning = check_for_warnings("uid", $_POST['uid']))) {
                $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
            }

            // Kod
            if (!strlen($_POST['code'])) {
                $warnings['code'][] = $lang->translate('field_no_empty');
            } else {
                if (strlen($_POST['code']) > 16) {
                    $warnings['code'][] = $lang->translate('return_code_length_warn');
                }
            }

            // Łączymy zwrócone błędy
            $warnings = array_merge(
                (array) $warnings,
                (array) $serviceModule->serviceCodeAdminAddValidate($_POST)
            );

            if ($warnings) {
                throw new ValidationException($warnings);
            }

            // Pozyskujemy dane kodu do dodania
            $codeData = $serviceModule->serviceCodeAdminAddInsert($_POST);

            $db->query(
                $db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "service_codes` " .
                        "SET `code` = '%s', `service` = '%s', `uid` = '%d', `server` = '%d', `amount` = '%d', `tariff` = '%d', `data` = '%s'",
                    [
                        $_POST['code'],
                        $serviceModule->service['id'],
                        if_strlen($_POST['uid'], 0),
                        if_isset($codeData['server'], 0),
                        if_isset($codeData['amount'], 0),
                        if_isset($codeData['tariff'], 0),
                        $codeData['data'],
                    ]
                )
            );

            log_info(
                $langShop->sprintf(
                    $langShop->translate('code_added_admin'),
                    $user->getUsername(),
                    $user->getUid(),
                    $_POST['code'],
                    $serviceModule->service['id']
                )
            );
            // Zwróć info o prawidłowym dodaniu
            return new ApiResponse('ok', $lang->translate('code_added'), 1);
        }

        if ($action == "delete_service_code") {
            if (!get_privileges("manage_service_codes")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "service_codes` " . "WHERE `id` = '%d'",
                    [$_POST['id']]
                )
            );

            // Zwróć info o prawidłowym lub błędnym usunięciu
            if ($db->affectedRows()) {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('code_deleted_admin'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['id']
                    )
                );
                return new ApiResponse('ok', $lang->translate('code_deleted'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('code_not_deleted'), 0);
        }

        if ($action == "delete_log") {
            if (!get_privileges("manage_logs")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $db->query(
                $db->prepare("DELETE FROM `" . TABLE_PREFIX . "logs` " . "WHERE `id` = '%d'", [
                    $_POST['id'],
                ])
            );

            // Zwróć info o prawidłowym lub błędnym usunieciu
            if ($db->affectedRows()) {
                return new ApiResponse('ok', $lang->translate('delete_log'), 1);
            }

            return new ApiResponse("not_deleted", $lang->translate('no_delete_log'), 0);
        }

        return new ApiResponse("script_error", "An error occured: no action.");
    }
}
