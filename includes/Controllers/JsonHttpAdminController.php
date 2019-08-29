<?php
namespace App\Controllers;

use App\Application;
use App\Auth;
use App\Database;
use App\Exceptions\SqlQueryException;
use App\Heart;
use App\Models\Purchase;
use App\Pages\Interfaces\IPageAdminActionBox;
use App\Repositories\PricelistRepository;
use App\Repositories\ServerRepository;
use App\Responses\ApiResponse;
use App\Responses\PlainResponse;
use App\Services\ChargeWallet\ServiceChargeWalletSimple;
use App\Settings;
use App\Template;
use App\TranslationManager;
use App\Services\Interfaces\IServiceActionExecute;
use App\Services\Interfaces\IServiceAdminManage;
use App\Services\Interfaces\IServiceAvailableOnServers;
use App\Services\Interfaces\IServiceServiceCodeAdminManage;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class JsonHttpAdminController
{
    public function action(
        Request $request,
        Application $app,
        Database $db,
        Heart $heart,
        Auth $auth,
        Settings $settings,
        Template $templates,
        TranslationManager $translationManager,
        ServerRepository $serverRepository,
        PricelistRepository $pricelistRepository
    ) {
        $langShop = $translationManager->shop();
        $lang = $translationManager->user();
        $user = $auth->user();

        // Pobranie akcji
        $action = $request->request->get("action");

        $warnings = [];

        $data = [];
        if ($action == "charge_wallet") {
            if (!get_privileges("manage_users")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $uid = $_POST['uid'];
            $amount = intval($_POST['amount'] * 100);

            // ID użytkownika
            if ($warning = check_for_warnings("uid", $uid)) {
                $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
            } else {
                $user2 = $heart->getUser($uid);
                if (!$user2->exists()) {
                    $warnings['uid'][] = $lang->translate('noaccount_id');
                }
            }

            // Wartość Doładowania
            if (!$amount) {
                $warnings['amount'][] = $lang->translate('no_charge_value');
            } else {
                if (!is_numeric($amount)) {
                    $warnings['amount'][] = $lang->translate('charge_number');
                }
            }

            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            // Zmiana wartości amount, aby stan konta nie zszedł poniżej zera
            $amount = max($amount, -$user2->getWallet());

            $serviceModule = $heart->getServiceModule(ServiceChargeWalletSimple::MODULE_ID);
            if (is_null($serviceModule)) {
                return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
            }

            // Dodawanie informacji o płatności do bazy
            $paymentId = pay_by_admin($user);

            // Kupujemy usługę
            $purchaseData = new Purchase();
            $purchaseData->user = $user2;
            $purchaseData->setPayment([
                'method' => "admin",
                'payment_id' => $paymentId,
            ]);
            $purchaseData->setOrder([
                'amount' => $amount,
            ]);
            $purchaseData->setEmail($user2->getEmail());

            $purchaseReturn = $serviceModule->purchase($purchaseData);

            log_info(
                $langShop->sprintf(
                    $langShop->translate('account_charge'),
                    $user->getUsername(),
                    $user->getUid(),
                    $user2->getUsername(),
                    $user2->getUid(),
                    number_format($amount / 100.0, 2),
                    $settings['currency']
                )
            );

            return new ApiResponse(
                "charged",
                $lang->sprintf(
                    $lang->translate('account_charge_success'),
                    $user2->getUsername(),
                    number_format($amount / 100.0, 2),
                    $settings['currency']
                ),
                1
            );
        }

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
                foreach ($returnData['data']['warnings'] as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $returnData['data']['warnings'][$brick] = $warning;
                }
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

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if ($returnData['status'] == "warnings") {
                foreach ($returnData['data']['warnings'] as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $returnData['data']['warnings'][$brick] = $warning;
                }
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

        if ($action == "user_service_add_form_get") {
            if (!get_privileges("manage_user_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $output = "";
            if (($serviceModule = $heart->getServiceModule($_POST['service'])) !== null) {
                $output = $serviceModule->userServiceAdminAddFormGet();
            }

            return new PlainResponse($output);
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

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
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
            $shop_url = $_POST['shop_url'];
            $sender_email = $_POST['sender_email'];
            $sender_email_name = $_POST['sender_email_name'];
            $signature = $_POST['signature'];
            $vat = $_POST['vat'];
            $contact = $_POST['contact'];
            $row_limit = $_POST['row_limit'];
            $licenseToken = $_POST['license_token'];
            $cron = $_POST['cron'];
            $language = escape_filename($_POST['language']);
            $theme = escape_filename($_POST['theme']);
            $date_format = $_POST['date_format'];
            $delete_logs = $_POST['delete_logs'];
            $google_analytics = trim($_POST['google_analytics']);
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
            if (strlen($sender_email) && ($warning = check_for_warnings("email", $sender_email))) {
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
            if ($warning = check_for_warnings("number", $delete_logs)) {
                $warnings['delete_logs'] = array_merge((array) $warnings['delete_logs'], $warning);
            }

            // Wierszy na stronę
            if ($warning = check_for_warnings("number", $row_limit)) {
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
            if (!is_dir($app->path("themes/{$theme}")) || $theme[0] == '.') {
                $warnings['theme'][] = $lang->translate('no_theme');
            }

            // Język
            if (!is_dir($app->path("includes/languages/{$language}")) || $language[0] == '.') {
                $warnings['language'][] = $lang->translate('no_language');
            }

            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
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
                        $shop_url,
                        $sender_email,
                        $sender_email_name,
                        $signature,
                        $vat,
                        $contact,
                        $row_limit,
                        $cron,
                        $_POST['user_edit_service'],
                        $theme,
                        $language,
                        $date_format,
                        $delete_logs,
                        $google_analytics,
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
            $transaction_service = $db->fetchArrayAssoc($result);
            $transaction_service['data'] = json_decode($transaction_service['data']);
            foreach ($transaction_service['data'] as $key => $value) {
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
                $additional_warnings = $serviceModule->serviceAdminManagePre($_POST);
                $warnings = array_merge((array) $warnings, (array) $additional_warnings);
            }

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            // Po błędach wywołujemy metodę modułu
            if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
                $module_data = $serviceModule->serviceAdminManagePost($_POST);

                // Tworzymy elementy SET zapytania
                if (isset($module_data['query_set'])) {
                    $set = '';
                    foreach ($module_data['query_set'] as $element) {
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

        if ($action == "get_service_module_extra_fields") {
            if (!get_privileges("manage_user_services")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $output = "";
            // Pobieramy moduł obecnie edytowanej usługi, jeżeli powróciliśmy do pierwotnego modułu
            // W przeciwnym razie pobieramy wybrany moduł
            if (
                is_null($serviceModule = $heart->getServiceModule($_POST['service'])) ||
                $serviceModule->getModuleId() != $_POST['module']
            ) {
                $serviceModule = $heart->getServiceModuleS($_POST['module']);
            }

            if ($serviceModule !== null && $serviceModule instanceof IServiceAdminManage) {
                $output = $serviceModule->serviceAdminExtraFieldsGet();
            }

            return new PlainResponse($output);
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

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            if ($action == "server_add") {
                $server = $serverRepository->create(
                    $_POST['name'],
                    $_POST['ip'],
                    $_POST['port'],
                    $_POST['sms_service']
                );
                $server_id = $server->getId();
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

                $server_id = $_POST['id'];
            }

            // Aktualizujemy powiazania serwerow z uslugami
            if ($server_id) {
                $servers_services = [];
                foreach ($heart->getServices() as $service) {
                    // Dana usługa nie może być kupiona na serwerze
                    if (
                        !is_null($serviceModule = $heart->getServiceModule($service['id'])) &&
                        !($serviceModule instanceof IServiceAvailableOnServers)
                    ) {
                        continue;
                    }

                    $servers_services[] = [
                        'service' => $service['id'],
                        'server' => $server_id,
                        'status' => (bool) $_POST[$service['id']],
                    ];
                }

                update_servers_services($servers_services);
            }

            if ($action == "server_add") {
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('server_admin_add'),
                        $user->getUsername(),
                        $user->getUid(),
                        $server_id
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
                        $server_id
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

        if ($action == "user_edit") {
            if (!get_privileges("manage_users")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $user2 = $heart->getUser($_POST['uid']);

            // Nazwa użytkownika
            if ($user2->getUsername() != $_POST['username']) {
                if ($warning = check_for_warnings("username", $_POST['username'])) {
                    $warnings['username'] = array_merge((array) $warnings['username'], $warning);
                }
                $result = $db->query(
                    $db->prepare(
                        "SELECT `uid` " .
                            "FROM `" .
                            TABLE_PREFIX .
                            "users` " .
                            "WHERE username = '%s'",
                        [$_POST['username']]
                    )
                );
                if ($db->numRows($result)) {
                    $warnings['username'][] = $lang->translate('nick_taken');
                }
            }

            // E-mail
            if ($user2->getEmail() != $_POST['email']) {
                if ($warning = check_for_warnings("email", $_POST['email'])) {
                    $warnings['email'] = array_merge((array) $warnings['email'], $warning);
                }
                $result = $db->query(
                    $db->prepare(
                        "SELECT `uid` " .
                            "FROM `" .
                            TABLE_PREFIX .
                            "users` " .
                            "WHERE email = '%s'",
                        [$_POST['email']]
                    )
                );
                if ($db->numRows($result)) {
                    $warnings['email'][] = $lang->translate('email_taken');
                }
            }

            // Grupy
            foreach ($_POST['groups'] as $gid) {
                if (is_null($heart->getGroup($gid))) {
                    $warnings['groups[]'][] = $lang->translate('wrong_group');
                    break;
                }
            }

            // Portfel
            if ($warning = check_for_warnings("number", $_POST['wallet'])) {
                $warnings['wallet'] = array_merge((array) $warnings['wallet'], $warning);
            }

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            $db->query(
                $db->prepare(
                    "UPDATE `" .
                        TABLE_PREFIX .
                        "users` " .
                        "SET `username` = '%s', `forename` = '%s', `surname` = '%s', `email` = '%s', `groups` = '%s', `wallet` = '%d' " .
                        "WHERE `uid` = '%d'",
                    [
                        $_POST['username'],
                        $_POST['forename'],
                        $_POST['surname'],
                        $_POST['email'],
                        implode(";", $_POST['groups']),
                        ceil($_POST['wallet'] * 100),
                        $_POST['uid'],
                    ]
                )
            );

            // Zwróć info o prawidłowej lub błędnej edycji
            if ($db->affectedRows()) {
                // LOGGING
                log_info(
                    $langShop->sprintf(
                        $langShop->translate('user_admin_edit'),
                        $user->getUsername(),
                        $user->getUid(),
                        $_POST['uid']
                    )
                );
                return new ApiResponse('ok', $lang->translate('user_edit'), 1);
            }
            return new ApiResponse("not_edited", $lang->translate('user_no_edit'), 0);
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

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
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

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
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

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            if ($action == "price_add") {
                $pricelistRepository->create(
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

            // Błędy
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
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

            $result = $db->query(
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

            // Przerabiamy ostrzeżenia, aby lepiej wyglądały
            if (!empty($warnings)) {
                foreach ($warnings as $brick => $warning) {
                    $warning = create_dom_element("div", implode("<br />", $warning), [
                        'class' => "form_warning",
                    ]);
                    $data['warnings'][$brick] = $warning;
                }
                return new ApiResponse("warnings", $lang->translate('form_wrong_filled'), 0, $data);
            }

            // Pozyskujemy dane kodu do dodania
            $code_data = $serviceModule->serviceCodeAdminAddInsert($_POST);

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
                        if_isset($code_data['server'], 0),
                        if_isset($code_data['amount'], 0),
                        if_isset($code_data['tariff'], 0),
                        $code_data['data'],
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

            $result = $db->query(
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

        if ($action == "service_code_add_form_get") {
            if (!get_privileges("manage_service_codes")) {
                return new ApiResponse(
                    "not_logged_in",
                    $lang->translate('not_logged_or_no_perm'),
                    0
                );
            }

            $output = "";
            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) !== null &&
                $serviceModule instanceof IServiceServiceCodeAdminManage
            ) {
                $output = $serviceModule->serviceCodeAdminAddFormGet();
            }

            return new PlainResponse($output);
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

        if ($action == "refresh_blocks") {
            if (isset($_POST['bricks'])) {
                $bricks = explode(";", $_POST['bricks']);
            }

            foreach ($bricks as $brick) {
                // Nie ma takiego bloku do odświeżenia
                if (($block = $heart->getBlock($brick)) === null) {
                    continue;
                }

                $data[$block->getContentId()]['content'] = $block->getContent(
                    $request->query->all(),
                    $request->request->all()
                );
                if ($data[$block->getContentId()]['content'] !== null) {
                    $data[$block->getContentId()]['class'] = $block->getContentClass();
                } else {
                    $data[$block->getContentId()]['class'] = "";
                }
            }

            return new PlainResponse(json_encode($data));
        }

        if ($action == "get_action_box") {
            if (!isset($_POST['page_id']) || !isset($_POST['box_id'])) {
                return new ApiResponse("no_data", $lang->translate('not_all_data'), 0);
            }

            if (($page = $heart->getPage($_POST['page_id'], "admin")) === null) {
                return new ApiResponse("wrong_page", $lang->translate('wrong_page_id'), 0);
            }

            if (!($page instanceof IPageAdminActionBox)) {
                return new ApiResponse(
                    "page_no_action_box",
                    $lang->translate('no_action_box_support'),
                    0
                );
            }

            $actionBox = $page->getActionBox($_POST['box_id'], $_POST);

            $data = [];
            if (strlen($actionBox['template'])) {
                $data['template'] = $actionBox['template'];
            }

            return new ApiResponse($actionBox['status'], $actionBox['text'], 0, $data);
        }

        if ($action == "get_template") {
            $template = $_POST['template'];
            // Zabezpieczanie wszystkich wartości post
            foreach ($_POST as $key => $value) {
                $_POST[$key] = htmlspecialchars($value);
            }

            if ($template == "admin_user_wallet") {
                if (!get_privileges("manage_users")) {
                    return new ApiResponse(
                        "not_logged_in",
                        $lang->translate('not_logged_or_no_perm'),
                        0
                    );
                }

                $user2 = $heart->getUser($_POST['uid']);
            }

            if (!isset($data['template'])) {
                $data['template'] = $templates->render("jsonhttp/" . $template, compact('user2'));
            }

            return new PlainResponse(json_encode($data));
        }

        if ($action == "service_action_execute") {
            if (
                ($serviceModule = $heart->getServiceModule($_POST['service'])) === null ||
                !($serviceModule instanceof IServiceActionExecute)
            ) {
                return new PlainResponse($lang->translate('bad_module'));
            }

            return new PlainResponse(
                $serviceModule->actionExecute($_POST['service_action'], $_POST)
            );
        }

        return new ApiResponse("script_error", "An error occured: no action.");
    }
}
