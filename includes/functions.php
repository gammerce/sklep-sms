<?php

use App\Auth;
use App\Database;
use App\Heart;
use App\Mailer;
use App\Models\Purchase;
use App\Models\User;
use App\Payment;
use App\Settings;
use App\TranslationManager;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Get the available container instance.
 *
 * @param string $abstract
 * @param array  $parameters
 * @return mixed|\Illuminate\Container\Container|\App\Application
 */
function app($abstract = null, array $parameters = [])
{
    if ($abstract === null) {
        return Container::getInstance();
    }

    return Container::getInstance()->make($abstract, $parameters);
}

/**
 * Sprawdza czy jesteśmy w adminowskiej części sklepu
 *
 * @return bool
 */
function admin_session()
{
    return app()->isAdminSession();
}

/**
 * Pobranie szablonu
 *
 * @param string     $output Zwartość do wyświetlenia
 * @param int|string $header String do użycia w funkcji header()
 */
function output_page($output, $header = 0)
{
    if (is_string($header)) {
        header($header);
    } else {
        switch ($header) {
            case 1:
                header('Content-type: text/plain; charset="UTF-8"');
                break;

            default:
                header('Content-type: text/html; charset="UTF-8"');
        }
    }

    die($output);
}

/**
 * Zwraca treść danego bloku
 *
 * @param string  $element
 * @param Request $request
 * @param bool    $withenvelope
 *
 * @return string
 */
function get_content($element, Request $request, $withenvelope = true)
{
    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    if (($block = $heart->get_block($element)) === null) {
        return "";
    }

    $get = $request->query->all();
    $post = $request->request->all();

    return $withenvelope ? $block->get_content_enveloped($get, $post) : $block->get_content($get, $post);
}

function get_row_limit($page, $row_limit = 0)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    $row_limit = $row_limit ? $row_limit : $settings['row_limit'];

    return ($page - 1) * $row_limit . "," . $row_limit;
}

function get_pagination($all, $current_page, $script, $get, $row_limit = 0)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    $row_limit = $row_limit ? $row_limit : $settings['row_limit'];

    // Wszystkich elementow jest mniej niz wymagana ilsoc na jednej stronie
    if ($all <= $row_limit) {
        return;
    }

    // Pobieramy ilosc stron
    $pages_amount = floor(max($all - 1, 0) / $row_limit) + 1;

    // Poprawiamy obecna strone, gdyby byla bledna
    if ($current_page > $pages_amount) {
        $current_page = -1;
    }

    // Usuwamy index "page"
    unset($get['page']);
    $get_string = "";

    // Tworzymy stringa z danych get
    foreach ($get as $key => $value) {
        if (strlen($get_string)) {
            $get_string .= "&";
        }

        $get_string .= urlencode($key) . "=" . urlencode($value);
    }
    if (strlen($get_string)) {
        $get_string = "?" . $get_string;
    }

    /*// Pierwsza strona
    $output = create_dom_element("a",1,array(
        'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=1",
        'class'	=> $current_page == 1 ? "current" : ""
    ))."&nbsp;";

    // 2 3 ...
    if( $current_page < 5 ) {
        // 2 3
        for($i = 2; $i <= 3; ++$i) {
            $output .= create_dom_element("a",$i,array(
                'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page={$i}"
            ))."&nbsp;";
        }

        // Trzy kropki
        $output .= create_dom_element("a","...",array(
                'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=".round(($pages_amount-3)/2)
        ))."&nbsp;";
    }
    // ...
    else {

    }

    // Ostatnia strona
    $output .= create_dom_element("a",$pages_amount,array(
        'href'	=> $script.$get_string.($get_string != "" ? "&" : "?")."page=".$pages_amount,
        'class'	=> $current_page == $pages_amount ? "current" : ""
    ))."&nbsp;";*/

    $output = "";
    $lp = 2;
    for ($i = 1, $dots = false; $i <= $pages_amount; ++$i) {
        if ($i != 1 && $i != $pages_amount && ($i < $current_page - $lp || $i > $current_page + $lp)) {
            if (!$dots) {
                if ($i < $current_page - $lp) {
                    $href = $script . $get_string . (strlen($get_string) ? "&" : "?") . "page=" . round(((1 + $current_page - $lp) / 2));
                } else {
                    if ($i > $current_page + $lp) {
                        $href = $script . $get_string . (strlen($get_string) ? "&" : "?") . "page=" . round((($current_page + $lp + $pages_amount) / 2));
                    }
                }

                $output .= create_dom_element("a", "...", [
                        'href' => $href,
                    ]) . "&nbsp;";
                $dots = true;
            }
            continue;
        }

        $output .= create_dom_element("a", $i, [
                'href'  => $href = $script . $get_string . (strlen($get_string) ? "&" : "?") .
                    "page=" . $i,
                'class' => $current_page == $i ? "current" : "",
            ]) . "&nbsp;";
        $dots = false;
    }

    return $output;
}

/* User functions */
/**
 * Sprawddza czy użytkownik jest zalogowany
 *
 * @return bool
 */
function is_logged()
{
    /** @var Auth $auth */
    $auth = app()->make(Auth::class);

    return $auth->check();
}

/**
 * @param string $which
 * @param User   $user
 *
 * @return bool
 */
function get_privilages($which, $user = null)
{
    // Jeżeli nie podano użytkownika
    if ($user === null) {
        /** @var Auth $auth */
        $auth = app()->make(Auth::class);
        $user = $auth->user();
    }

    if ($user === null) {
        return false;
    }

    if (in_array($which, [
            "manage_settings",
            "view_groups",
            "manage_groups",
            "view_player_flags",
            "view_user_services",
            "manage_user_services",
            "view_income",
            "view_users",
            "manage_users",
            "view_sms_codes",
            "manage_sms_codes",
            "view_service_codes",
            "manage_service_codes",
            "view_antispam_questions",
            "manage_antispam_questions",
            "view_services",
            "manage_services",
            "view_servers",
            "manage_servers",
            "view_logs",
            "manage_logs",
            "update",
        ]
    )) {
        return $user->getPrivilages('acp') && $user->getPrivilages($which);
    }

    return $user->getPrivilages($which);
}

/**
 * @param int $uid
 * @param int $amount
 */
function charge_wallet($uid, $amount)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $db->query($db->prepare(
        "UPDATE `" . TABLE_PREFIX . "users` " .
        "SET `wallet` = `wallet` + '%d' " .
        "WHERE `uid` = '%d'",
        [$amount, $uid]
    ));
}

/**
 * Aktualizuje tabele servers_services
 *
 * @param $data
 */
function update_servers_services($data)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $delete = [];
    $add = [];
    foreach ($data as $arr) {
        if ($arr['status']) {
            $add[] = $db->prepare(
                "('%d', '%s')",
                [$arr['server'], $arr['service']]
            );
        } else {
            $delete[] = $db->prepare(
                "(`server_id` = '%d' AND `service_id` = '%s')",
                [$arr['server'], $arr['service']]
            );
        }
    }

    if (!empty($add)) {
        $db->query(
            "INSERT IGNORE INTO `" . TABLE_PREFIX . "servers_services` (`server_id`, `service_id`) " .
            "VALUES " . implode(", ", $add)
        );
    }

    if (!empty($delete)) {
        $db->query(
            "DELETE FROM `" . TABLE_PREFIX . "servers_services` " .
            "WHERE " . implode(" OR ", $delete)
        );
    }
}

/**
 * @param Purchase $purchase_data
 *
 * @return array
 */
function validate_payment($purchase_data)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    $warnings = [];

    // Tworzymy obiekt usługi którą kupujemy
    if (($service_module = $heart->get_service_module($purchase_data->getService())) === null) {
        return [
            'status'   => "wrong_module",
            'text'     => $lang->translate('bad_module'),
            'positive' => false,
        ];
    }

    if (!in_array($purchase_data->getPayment('method'), ["sms", "transfer", "wallet", "service_code"])) {
        return [
            'status'   => "wrong_method",
            'text'     => $lang->translate('wrong_payment_method'),
            'positive' => false,
        ];
    }

    // Tworzymy obiekt, który będzie nam obsługiwał proces płatności
    if ($purchase_data->getPayment('method') == "sms") {
        $transaction_service = if_strlen2($purchase_data->getPayment('sms_service'), $settings['sms_service']);
        $payment = new Payment($transaction_service);
    } else {
        if ($purchase_data->getPayment('method') == "transfer") {
            $transaction_service = if_strlen2($purchase_data->getPayment('transfer_service'),
                $settings['transfer_service']);
            $payment = new Payment($transaction_service);
        }
    }

    // Pobieramy ile kosztuje ta usługa dla przelewu / portfela
    if ($purchase_data->getPayment('cost') === null) {
        $purchase_data->setPayment([
            'cost' => $purchase_data->getTariff()->getProvision(),
        ]);
    }

    // Metoda płatności
    if ($purchase_data->getPayment('method') == "wallet" && !is_logged()) {
        return [
            'status'   => "wallet_not_logged",
            'text'     => $lang->translate('no_login_no_wallet'),
            'positive' => false,
        ];
    } elseif ($purchase_data->getPayment('method') == "transfer") {
        if ($purchase_data->getPayment('cost') <= 1) {
            return [
                'status'   => "too_little_for_transfer",
                'text'     => $lang->sprintf($lang->translate('transfer_above_amount'), $settings['currency']),
                'positive' => false,
            ];
        }

        if (!$payment->getPaymentModule()->supportTransfer()) {
            return [
                'status'   => "transfer_unavailable",
                'text'     => $lang->translate('transfer_unavailable'),
                'positive' => false,
            ];
        }
    } elseif ($purchase_data->getPayment('method') == "sms" && !$payment->getPaymentModule()->supportSms()) {
        return [
            'status'   => "sms_unavailable",
            'text'     => $lang->translate('sms_unavailable'),
            'positive' => false,
        ];
    } elseif ($purchase_data->getPayment('method') == "sms" && $purchase_data->getTariff() === null) {
        return [
            'status'   => "no_sms_option",
            'text'     => $lang->translate('no_sms_payment'),
            'positive' => false,
        ];
    }

    // Kod SMS
    $purchase_data->setPayment([
        'sms_code' => trim($purchase_data->getPayment('sms_code')),
    ]);
    if ($purchase_data->getPayment('method') == "sms" && $warning = check_for_warnings("sms_code",
            $purchase_data->getPayment('sms_code'))) {
        $warnings['sms_code'] = array_merge((array)$warnings['sms_code'], $warning);
    }

    // Kod na usługę
    if ($purchase_data->getPayment('method') == "service_code") {
        if (!strlen($purchase_data->getPayment('service_code'))) {
            $warnings['service_code'][] = $lang->translate('field_no_empty');
        }
    }

    // Błędy
    if (!empty($warnings)) {
        $warning_data = [];
        foreach ($warnings as $brick => $warning) {
            $warning = create_dom_element("div", implode("<br />", $warning), [
                'class' => "form_warning",
            ]);
            $warning_data['warnings'][$brick] = $warning;
        }

        return [
            'status'   => "warnings",
            'text'     => $lang->translate('form_wrong_filled'),
            'positive' => false,
            'data'     => $warning_data,
        ];
    }

    if ($purchase_data->getPayment('method') == "sms") {
        // Sprawdzamy kod zwrotny
        $sms_return = $payment->pay_sms(
            $purchase_data->getPayment('sms_code'), $purchase_data->getTariff(), $purchase_data->user
        );
        $payment_id = $sms_return['payment_id'];

        if ($sms_return['status'] != IPayment_Sms::OK) {
            return [
                'status'   => $sms_return['status'],
                'text'     => $sms_return['text'],
                'positive' => false,
            ];
        }
    } elseif ($purchase_data->getPayment('method') == "wallet") {
        // Dodanie informacji o płatności z portfela
        $payment_id = pay_wallet($purchase_data->getPayment('cost'), $purchase_data->user);

        // Metoda pay_wallet zwróciła błąd.
        if (is_array($payment_id)) {
            return $payment_id;
        }
    } elseif ($purchase_data->getPayment('method') == "service_code") {
        // Dodanie informacji o płatności z portfela
        $payment_id = pay_service_code($purchase_data, $service_module);

        // Funkcja pay_service_code zwróciła błąd.
        if (is_array($payment_id)) {
            return $payment_id;
        }
    }

    if (in_array($purchase_data->getPayment('method'), ["wallet", "sms", "service_code"])) {
        // Dokonujemy zakupu usługi
        $purchase_data->setPayment([
            'payment_id' => $payment_id,
        ]);
        $bought_service_id = $service_module->purchase($purchase_data);

        return [
            'status'   => "purchased",
            'text'     => $lang->translate('purchase_success'),
            'positive' => true,
            'data'     => ['bsid' => $bought_service_id],
        ];
    } elseif ($purchase_data->getPayment('method') == "transfer") {
        $purchase_data->setDesc(
            $lang->sprintf($lang->translate('payment_for_service'), $service_module->service['name'])
        );

        return $payment->pay_transfer($purchase_data);
    }
}

/**
 * @param User $user_admin
 *
 * @return int|string
 */
function pay_by_admin($user_admin)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    // Dodawanie informacji o płatności
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "payment_admin` (`aid`, `ip`, `platform`) " .
        "VALUES ('%d', '%s', '%s')",
        [$user_admin->getUid(), $user_admin->getLastIp(), $user_admin->getPlatform()]
    ));

    return $db->last_id();
}

/**
 * @param int  $cost
 * @param User $user
 *
 * @return array|int|string
 */
function pay_wallet($cost, $user)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    /** @var Database $db */
    $db = app()->make(Database::class);

    // Sprawdzanie, czy jest wystarczająca ilość kasy w portfelu
    if ($cost > $user->getWallet()) {
        return [
            'status'   => "no_money",
            'text'     => $lang->translate('not_enough_money'),
            'positive' => false,
        ];
    }

    // Zabieramy kasę z portfela
    charge_wallet($user->getUid(), -$cost);

    // Dodajemy informacje o płatności portfelem
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "payment_wallet` " .
        "SET `cost` = '%d', `ip` = '%s', `platform` = '%s'",
        [$cost, $user->getLastIp(), $user->getPlatform()]
    ));

    return $db->last_id();
}

/**
 * @param Purchase                                                   $purchase_data
 * @param Service|ServiceChargeWallet|ServiceExtraFlags|ServiceOther $service_module
 *
 * @return array|int|string
 */
function pay_service_code($purchase_data, $service_module)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();
    $langShop = $translationManager->shop();

    /** @var Database $db */
    $db = app()->make(Database::class);

    $result = $db->query($db->prepare(
        "SELECT * FROM `" . TABLE_PREFIX . "service_codes` " .
        "WHERE `code` = '%s' " .
        "AND `service` = '%s' " .
        "AND (`server` = '0' OR `server` = '%s') " .
        "AND (`tariff` = '0' OR `tariff` = '%d') " .
        "AND (`uid` = '0' OR `uid` = '%s')",
        [
            $purchase_data->getPayment('service_code'),
            $purchase_data->getService(),
            $purchase_data->getOrder('server'),
            $purchase_data->getTariff(),
            $purchase_data->user->getUid(),
        ]
    ));

    while ($row = $db->fetch_array_assoc($result)) {
        if ($service_module->service_code_validate($purchase_data, $row)) { // Znalezlismy odpowiedni kod
            $db->query($db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "service_codes` " .
                "WHERE `id` = '%d'",
                [$row['id']]
            ));

            // Dodajemy informacje o płatności kodem
            $db->query($db->prepare(
                "INSERT INTO `" . TABLE_PREFIX . "payment_code` " .
                "SET `code` = '%s', `ip` = '%s', `platform` = '%s'",
                [
                    $purchase_data->getPayment('service_code'),
                    $purchase_data->user->getLastip(),
                    $purchase_data->user->getPlatform(),
                ]
            ));
            $payment_id = $db->last_id();

            log_info($langShop->sprintf($langShop->translate('purchase_code'),
                $purchase_data->getPayment('service_code'),
                $purchase_data->user->getUsername(), $purchase_data->user->getUid(), $payment_id));

            return $payment_id;
        }
    }

    return [
        'status'   => "wrong_service_code",
        'text'     => $lang->translate('bad_service_code'),
        'positive' => false,
    ];
}

/**
 * Add information about purchasing a service
 *
 * @param integer $uid
 * @param string  $user_name
 * @param string  $ip
 * @param string  $method
 * @param string  $payment_id
 * @param string  $service
 * @param integer $server
 * @param string  $amount
 * @param string  $auth_data
 * @param string  $email
 * @param array   $extra_data
 *
 * @return int|string
 */
function add_bought_service_info(
    $uid,
    $user_name,
    $ip,
    $method,
    $payment_id,
    $service,
    $server,
    $amount,
    $auth_data,
    $email,
    $extra_data = []
) {
    /** @var Database $db */
    $db = app()->make(Database::class);

    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();
    $langShop = $translationManager->shop();

    /** @var Heart $heart */
    $heart = app()->make(Heart::class);
    /** @var Mailer $mailer */
    $mailer = app()->make(Mailer::class);

    // Dodajemy informacje o kupionej usludze do bazy danych
    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "bought_services` " .
        "SET `uid` = '%d', `payment` = '%s', `payment_id` = '%s', `service` = '%s', " .
        "`server` = '%d', `amount` = '%s', `auth_data` = '%s', `email` = '%s', `extra_data` = '%s'",
        [$uid, $method, $payment_id, $service, $server, $amount, $auth_data, $email, json_encode($extra_data)]
    ));
    $bougt_service_id = $db->last_id();

    $ret = $lang->translate('none');
    if (strlen($email)) {
        $message = purchase_info([
            'purchase_id' => $bougt_service_id,
            'action'      => "email",
        ]);
        if (strlen($message)) {
            $title = ($service == 'charge_wallet' ? $lang->translate('charge_wallet') : $lang->translate('purchase'));
            $ret = $mailer->send($email, $auth_data, $title, $message);
        }

        if ($ret == "not_sent") {
            $ret = "nie wysłano";
        } else {
            if ($ret == "sent") {
                $ret = "wysłano";
            }
        }
    }

    $temp_service = $heart->get_service($service);
    $temp_server = $heart->get_server($server);
    $amount = $amount != -1 ? "{$amount} {$temp_service['tag']}" : $lang->translate('forever');
    log_info($langShop->sprintf(
        $langShop->translate('bought_service_info'),
        $service,
        $auth_data,
        $amount,
        $temp_server['name'],
        $payment_id,
        $ret,
        $user_name,
        $uid,
        $ip
    ));
    unset($temp_server);

    return $bougt_service_id;
}

//
// $data:
// 	purchase_id - id zakupu
// 	payment - metoda płatności
// 	payment_id - id płatności
// 	action - jak sformatowac dane
//
function purchase_info($data)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    // Wyszukujemy po id zakupu
    if (isset($data['purchase_id'])) {
        $where = $db->prepare("t.id = '%d'", [$data['purchase_id']]);
    } // Wyszukujemy po id płatności
    else {
        if (isset($data['payment']) && isset($data['payment_id'])) {
            $where = $db->prepare(
                "t.payment = '%s' AND t.payment_id = '%s'",
                [$data['payment'], $data['payment_id']]
            );
        } else {
            return "";
        }
    }

    $pbs = $db->fetch_array_assoc($db->query(
        "SELECT * FROM ({$settings['transactions_query']}) as t " .
        "WHERE {$where}"
    ));

    // Brak wynikow
    if (empty($pbs)) {
        return "Brak zakupu w bazie.";
    }

    $service_module = $heart->get_service_module($pbs['service']);

    return $service_module !== null && object_implements($service_module,
        "IService_PurchaseWeb") ? $service_module->purchase_info($data['action'], $pbs) : "";
}

/**
 * Pozyskuje z bazy wszystkie usługi użytkowników
 *
 * @param string|int $conditions Jezeli jest tylko jeden element w tablicy, to zwroci ten element zamiast tablicy
 * @param bool       $take_out
 *
 * @return array
 */
function get_users_services($conditions = '', $take_out = true)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    if (my_is_integer($conditions)) {
        $conditions = "WHERE `id` = " . intval($conditions);
    }

    $output = $used_table = [];
    // Niestety dla każdego modułu musimy wykonać osobne zapytanie :-(
    foreach ($heart->get_services_modules() as $service_module_data) {
        $table = $service_module_data['classsimple']::USER_SERVICE_TABLE;
        if (!strlen($table) || array_key_exists($table, $used_table)) {
            continue;
        }

        $result = $db->query(
            "SELECT us.*, m.*, UNIX_TIMESTAMP() AS `now` FROM `" . TABLE_PREFIX . "user_service` AS us " .
            "INNER JOIN `" . TABLE_PREFIX . $table . "` AS m ON m.us_id = us.id " .
            $conditions .
            " ORDER BY us.id DESC "
        );

        while ($row = $db->fetch_array_assoc($result)) {
            unset($row['us_id']);
            $output[$row['id']] = $row;
        }

        $used_table[$table] = true;
    }

    ksort($output);
    $output = array_reverse($output);

    return $take_out && count($output) == 1 ? $output[0] : $output;
}

function delete_users_old_services()
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $langShop = $translationManager->shop();

    /** @var Database $db */
    $db = app()->make(Database::class);

    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    // Usunięcie przestarzałych usług użytkownika
    // Pierwsze pobieramy te, które usuniemy
    // Potem wywolujemy akcje na module, potem je usuwamy, a następnie wywołujemy akcje na module

    $delete_ids = $users_services = [];
    foreach (get_users_services("WHERE `expire` != '-1' AND `expire` < UNIX_TIMESTAMP()") as $user_service) {
        if (($service_module = $heart->get_service_module($user_service['service'])) === null) {
            continue;
        }

        if ($service_module->user_service_delete($user_service, 'task')) {
            $delete_ids[] = $user_service['id'];
            $users_services[] = $user_service;

            $user_service_desc = '';
            foreach ($user_service as $key => $value) {
                if (strlen($user_service_desc)) {
                    $user_service_desc .= ' ; ';
                }

                $user_service_desc .= ucfirst(strtolower($key)) . ': ' . $value;
            }

            log_info($langShop->sprintf($langShop->translate('expired_service_delete'), $user_service_desc));
        }
    }

    // Usuwamy usugi ktre zwróciły true
    if (!empty($delete_ids)) {
        $db->query(
            "DELETE FROM `" . TABLE_PREFIX . "user_service` " .
            "WHERE `id` IN (" . implode(", ", $delete_ids) . ")"
        );
    }

    // Wywołujemy akcje po usunieciu
    foreach ($users_services as $user_service) {
        if (($service_module = $heart->get_service_module($user_service['service'])) === null) {
            continue;
        }

        $service_module->user_service_delete_post($user_service);
    }
}

function log_info($string)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $db->query($db->prepare(
        "INSERT INTO `" . TABLE_PREFIX . "logs` " .
        "SET `text` = '%s'",
        [$string]
    ));
}

/**
 * Sprawdza, czy dany obiekt implementuje odpowiedni interfejs
 *
 * @param $class
 * @param $interface
 *
 * @return bool
 */
function object_implements($class, $interface)
{
    $interfaces = class_implements($class);

    return in_array($interface, $interfaces);
}

function create_dom_element($name, $text = "", $data = [])
{
    $features = "";
    foreach ($data as $key => $value) {
        if (is_array($value) || !strlen($value)) {
            continue;
        }

        $features .= (strlen($features) ? " " : "") . $key . '="' . str_replace('"', '\"', $value) . '"';
    }

    if (isset($data['style'])) {
        $style = '';
        foreach ($data['style'] as $key => $value) {
            if (!strlen($value)) {
                continue;
            }

            $style .= (strlen($style) ? "; " : "") . "{$key}: {$value}";
        }
        if (strlen($style)) {
            $features .= (strlen($features) ? " " : "") . "style=\"{$style}\"";
        }
    }

    $name_hsafe = htmlspecialchars($name);
    $output = "<{$name_hsafe} {$features}>";
    if (strlen($text)) {
        $output .= $text;
    }

    if (!in_array($name, ["input", "img"])) {
        $output .= "</{$name_hsafe}>";
    }

    return $output;
}

function create_brick($text, $class = "", $alpha = 0.2)
{
    $brick_r = rand(0, 255);
    $brick_g = rand(0, 255);
    $brick_b = rand(0, 255);

    return create_dom_element("div", $text, [
        'class' => "brick" . ($class ? " {$class}" : ""),
        'style' => [
            'border-color'     => "rgb({$brick_r},{$brick_g},{$brick_b})",
            'background-color' => "rgba({$brick_r},{$brick_g},{$brick_b},{$alpha})",
        ],
    ]);
}

function get_platform($platform)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    if ($platform == "engine_amxx") {
        return $lang->translate('amxx_server');
    } else {
        if ($platform == "engine_sm") {
            return $lang->translate('sm_server');
        }
    }

    return htmlspecialchars($platform);
}

function get_ip()
{
    /** @var Request $request */
    $request = app()->make(Request::class);

    if ($request->server->has('HTTP_CF_CONNECTING_IP')) {
        $cf_ip_ranges = [
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '104.16.0.0/12',
            '108.162.192.0/18',
            '131.0.72.0/22',
            '141.101.64.0/18',
            '162.158.0.0/15',
            '172.64.0.0/13',
            '173.245.48.0/20',
            '188.114.96.0/20',
            '190.93.240.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
        ];

        foreach ($cf_ip_ranges as $range) {
            if (ip_in_range($request->server->get('REMOTE_ADDR'), $range)) {
                return $request->server->get('HTTP_CF_CONNECTING_IP');
            }
        }
    }

    return $request->server->get('REMOTE_ADDR');
}

/**
 * Zwraca datę w odpowiednim formacie
 *
 * @param integer|string $timestamp
 * @param string         $format
 *
 * @return string
 */
function convertDate($timestamp, $format = "")
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    if (!strlen($format)) {
        $format = $settings['date_format'];
    }

    $date = new DateTime($timestamp);

    return $date->format($format);
}

/**
 * Returns sms cost netto by number
 *
 * @param string $number
 *
 * @return int
 */
function get_sms_cost($number)
{
    if (strlen($number) < 4) {
        return 0;
    } else {
        if ($number[0] == "7") {
            return $number[1] == "0" ? 50 : intval($number[1]) * 100;
        } else {
            if ($number[0] == "9") {
                return intval($number[1] . $number[2]) * 100;
            }
        }
    }

    return 0;
}

/**
 * Returns sms cost brutto by number
 *
 * @param $number
 *
 * @return float
 */
function get_sms_cost_brutto($number)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    return ceil(get_sms_cost($number) * $settings['vat']);
}

function hash_password($password, $salt)
{
    return md5(md5($password) . md5($salt));
}

function escape_filename($filename)
{
    $filename = str_replace('/', '_', $filename);
    $filename = str_replace(' ', '_', $filename);
    $filename = str_replace('.', '_', $filename);

    return $filename;
}

function get_random_string($length)
{
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890"; //length:36
    $final_rand = "";
    for ($i = 0; $i < $length; $i++) {
        $final_rand .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $final_rand;
}

function valid_steam($steamid)
{
    return preg_match('/\bSTEAM_([0-9]{1}):([0-9]{1}):([0-9])+$/', $steamid) ? '1' : '0';
}

function secondsToTime($seconds)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();

    $dtF = new DateTime("@0");
    $dtT = new DateTime("@$seconds");

    return $dtF->diff($dtT)->format("%a {$lang->translate('days')} {$lang->translate('and')} %h {$lang->translate('hours')}");
}

function if_isset(&$isset, $default)
{
    return isset($isset) ? $isset : $default;
}

function if_strlen(&$empty, $default)
{
    return isset($empty) && strlen($empty) ? $empty : $default;
}

function if_strlen2($empty, $default)
{
    return strlen($empty) ? $empty : $default;
}

function mb_str_split($string)
{
    return preg_split('/(?<!^)(?!$)/u', $string);
}

function searchWhere($search_ids, $search, &$where)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $search_where = [];
    $search_like = $db->escape('%' . implode('%', mb_str_split($search)) . '%');

    foreach ($search_ids as $search_id) {
        $search_where[] = "{$search_id} LIKE '{$search_like}'";
    }

    if (!empty($search_where)) {
        $search_where = implode(" OR ", $search_where);
        if (strlen($where)) {
            $where .= " AND ";
        }

        $where .= "( {$search_where} )";
    }
}

// ip_in_range
// This function takes 2 arguments, an IP address and a "range" in several
// different formats.
// Network ranges can be specified as:
// 1. Wildcard format:     1.2.3.*
// 2. CIDR format:         1.2.3/24  OR  1.2.3.4/255.255.255.0
// 3. Start-End IP format: 1.2.3.0-1.2.3.255
// The function will return true if the supplied IP is within the range.
// Note little validation is done on the range inputs - it expects you to
// use one of the above 3 formats.
function ip_in_range($ip, $range)
{
    if (strpos($range, '/') !== false) {
        // $range is in IP/NETMASK format
        list($range, $netmask) = explode('/', $range, 2);
        if (strpos($netmask, '.') !== false) {
            // $netmask is a 255.255.0.0 format
            $netmask = str_replace('*', '0', $netmask);
            $netmask_dec = ip2long($netmask);

            return ((ip2long($ip) & $netmask_dec) == (ip2long($range) & $netmask_dec));
        } else {
            // $netmask is a CIDR size block
            // fix the range argument
            $x = explode('.', $range);
            while (count($x) < 4) {
                $x[] = '0';
            }
            list($a, $b, $c, $d) = $x;
            $range = sprintf("%u.%u.%u.%u", empty($a) ? '0' : $a, empty($b) ? '0' : $b, empty($c) ? '0' : $c,
                empty($d) ? '0' : $d);
            $range_dec = ip2long($range);
            $ip_dec = ip2long($ip);

            # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
            #$netmask_dec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

            # Strategy 2 - Use math to create it
            $wildcard_dec = pow(2, (32 - $netmask)) - 1;
            $netmask_dec = ~$wildcard_dec;

            return (($ip_dec & $netmask_dec) == ($range_dec & $netmask_dec));
        }
    } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
        if (strpos($range, '*') !== false) { // a.b.*.* format
            // Just convert to A-B format by setting * to 0 for A and 255 for B
            $lower = str_replace('*', '0', $range);
            $upper = str_replace('*', '255', $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, '-') !== false) { // A-B format
            list($lower, $upper) = explode('-', $range, 2);
            $lower_dec = (float)sprintf("%u", ip2long($lower));
            $upper_dec = (float)sprintf("%u", ip2long($upper));
            $ip_dec = (float)sprintf("%u", ip2long($ip));

            return (($ip_dec >= $lower_dec) && ($ip_dec <= $upper_dec));
        }

        return false;
    }
}

function ends_at($string, $end)
{
    return substr($string, -strlen($end)) == $end;
}

function starts_with($haystack, $needle)
{
    return substr($haystack, 0, strlen($needle)) === (string)$needle;
}

function str_contains($string, $needle)
{
    return strpos($string, $needle) !== false;
}

/**
 * Prints var_dump in pre
 *
 * @param mixed $a
 */
function pr($a)
{
    echo "<pre>";
    var_dump($a);
    echo "</pre>";
}

/**
 * @param mixed $val
 *
 * @return bool
 */
function my_is_integer($val)
{
    return strlen($val) && trim($val) === strval(intval($val));
}

/**
 * @param string $glue
 * @param array  $stack
 *
 * @return string
 */
function implode_esc($glue, $stack)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $output = '';
    foreach ($stack as $value) {
        if (strlen($output)) {
            $output .= $glue;
        }

        $output .= $db->prepare("'%s'", [$value]);
    }

    return $output;
}

function log_to_file($file, $message)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    $text = date($settings['date_format']) . ": " . $message;

    if (file_exists($file) && strlen(file_get_contents($file))) {
        $text = file_get_contents($file) . "\n" . $text;
    }

    file_put_contents($file, $text);
}

function array_get($array, $key, $default = null)
{
    if (is_null($key)) {
        return $array;
    }

    if (isset($array[$key])) {
        return $array[$key];
    }

    foreach (explode('.', $key) as $segment) {
        if (!is_array($array) || !array_key_exists($segment, $array)) {
            return $default;
        }

        $array = $array[$segment];
    }

    return $array;
}

function captureRequest()
{
    $queryAttributes = [];
    foreach ($_GET as $key => $value) {
        $queryAttributes[$key] = urldecode($value);
    }

    $request = Symfony\Component\HttpFoundation\Request::createFromGlobals();
    $request->query->replace($queryAttributes);

    return $request;
}