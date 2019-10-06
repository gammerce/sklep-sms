<?php

use App\Auth;
use App\Database;
use App\Heart;
use App\Mailer;
use App\Models\Purchase;
use App\Models\User;
use App\Payment;
use App\Routes\UrlGenerator;
use App\Services\ChargeWallet\ServiceChargeWallet;
use App\Services\ExtraFlags\ServiceExtraFlags;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Other\ServiceOther;
use App\Services\Service;
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

    return Container::getInstance()->makeWith($abstract, $parameters);
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

    header("Expires: Sat, 1 Jan 2000 01:00:00 GMT");
    header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
    header("Cache-Control: no-cache, must-revalidate");
    header("Pragma: no-cache");

    die($output);
}

/**
 * Zwraca treść danego bloku
 *
 * @param string  $element
 * @param Request $request
 * @param bool    $withEnvelope
 *
 * @return string
 */
function get_content($element, Request $request, $withEnvelope = true)
{
    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    if (($block = $heart->getBlock($element)) === null) {
        return "";
    }

    $query = $request->query->all();
    $body = $request->request->all();

    return $withEnvelope
        ? $block->getContentEnveloped($query, $body)
        : $block->getContent($query, $body);
}

function get_row_limit($page, $rowLimit = 0)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    $rowLimit = $rowLimit ? $rowLimit : $settings['row_limit'];

    return ($page - 1) * $rowLimit . "," . $rowLimit;
}

function get_pagination($all, $currentPage, $script, $query, $rowLimit = 0)
{
    /** @var Settings $settings */
    $settings = app()->make(Settings::class);

    /** @var UrlGenerator $url */
    $url = app()->make(UrlGenerator::class);

    $rowLimit = $rowLimit ? $rowLimit : $settings['row_limit'];

    // Wszystkich elementow jest mniej niz wymagana ilsoc na jednej stronie
    if ($all <= $rowLimit) {
        return;
    }

    // Pobieramy ilosc stron
    $pagesAmount = floor(max($all - 1, 0) / $rowLimit) + 1;

    // Poprawiamy obecna strone, gdyby byla bledna
    if ($currentPage > $pagesAmount) {
        $currentPage = -1;
    }

    // Usuwamy index "page"
    unset($query['page']);
    $queryString = "";

    // Tworzymy stringa z danych query
    foreach ($query as $key => $value) {
        if (strlen($queryString)) {
            $queryString .= "&";
        }

        $queryString .= urlencode($key) . "=" . urlencode($value);
    }
    if (strlen($queryString)) {
        $queryString = "?" . $queryString;
    }

    /*// Pierwsza strona
    $output = create_dom_element("a",1,array(
        'href'	=> $script.$queryString.($queryString != "" ? "&" : "?")."page=1",
        'class'	=> $currentPage == 1 ? "current" : ""
    ))."&nbsp;";

    // 2 3 ...
    if( $currentPage < 5 ) {
        // 2 3
        for($i = 2; $i <= 3; ++$i) {
            $output .= create_dom_element("a",$i,array(
                'href'	=> $script.$queryString.($queryString != "" ? "&" : "?")."page={$i}"
            ))."&nbsp;";
        }

        // Trzy kropki
        $output .= create_dom_element("a","...",array(
                'href'	=> $script.$queryString.($queryString != "" ? "&" : "?")."page=".round(($pagesAmount-3)/2)
        ))."&nbsp;";
    }
    // ...
    else {

    }

    // Ostatnia strona
    $output .= create_dom_element("a",$pagesAmount,array(
        'href'	=> $script.$queryString.($queryString != "" ? "&" : "?")."page=".$pagesAmount,
        'class'	=> $currentPage == $pagesAmount ? "current" : ""
    ))."&nbsp;";*/

    $output = "";
    $lp = 2;
    for ($i = 1, $dots = false; $i <= $pagesAmount; ++$i) {
        if ($i != 1 && $i != $pagesAmount && ($i < $currentPage - $lp || $i > $currentPage + $lp)) {
            if (!$dots) {
                if ($i < $currentPage - $lp) {
                    $href = $url->to(
                        $script .
                            $queryString .
                            (strlen($queryString) ? "&" : "?") .
                            "page=" .
                            round((1 + $currentPage - $lp) / 2)
                    );
                } elseif ($i > $currentPage + $lp) {
                    $href = $url->to(
                        $script .
                            $queryString .
                            (strlen($queryString) ? "&" : "?") .
                            "page=" .
                            round(($currentPage + $lp + $pagesAmount) / 2)
                    );
                }

                $output .=
                    create_dom_element("a", "...", [
                        'href' => $href,
                    ]) . "&nbsp;";
                $dots = true;
            }
            continue;
        }

        $output .=
            create_dom_element("a", $i, [
                'href' => ($href = $url->to(
                    $script . $queryString . (strlen($queryString) ? "&" : "?") . "page=" . $i
                )),
                'class' => $currentPage == $i ? "current" : "",
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
 * @param string $privilege
 * @param User   $user
 *
 * @return bool
 */
function get_privileges($privilege, $user = null)
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

    $adminPrivileges = [
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
    ];

    if (in_array($privilege, $adminPrivileges)) {
        return $user->getPrivileges('acp') && $user->getPrivileges($privilege);
    }

    return $user->getPrivileges($privilege);
}

/**
 * @param int $uid
 * @param int $amount
 */
function charge_wallet($uid, $amount)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $db->query(
        $db->prepare(
            "UPDATE `" .
                TABLE_PREFIX .
                "users` " .
                "SET `wallet` = `wallet` + '%d' " .
                "WHERE `uid` = '%d'",
            [$amount, $uid]
        )
    );
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
            $add[] = $db->prepare("('%d', '%s')", [$arr['server'], $arr['service']]);
        } else {
            $delete[] = $db->prepare("(`server_id` = '%d' AND `service_id` = '%s')", [
                $arr['server'],
                $arr['service'],
            ]);
        }
    }

    if (!empty($add)) {
        $db->query(
            "INSERT IGNORE INTO `" .
                TABLE_PREFIX .
                "servers_services` (`server_id`, `service_id`) " .
                "VALUES " .
                implode(", ", $add)
        );
    }

    if (!empty($delete)) {
        $db->query(
            "DELETE FROM `" .
                TABLE_PREFIX .
                "servers_services` " .
                "WHERE " .
                implode(" OR ", $delete)
        );
    }
}

/**
 * @param Purchase $purchaseData
 *
 * @return array
 */
function validate_payment(Purchase $purchaseData)
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
    if (($serviceModule = $heart->getServiceModule($purchaseData->getService())) === null) {
        return [
            'status' => "wrong_module",
            'text' => $lang->translate('bad_module'),
            'positive' => false,
        ];
    }

    if (
        !in_array($purchaseData->getPayment('method'), [
            "sms",
            "transfer",
            "wallet",
            "service_code",
        ])
    ) {
        return [
            'status' => "wrong_method",
            'text' => $lang->translate('wrong_payment_method'),
            'positive' => false,
        ];
    }

    // Tworzymy obiekt, który będzie nam obsługiwał proces płatności
    if ($purchaseData->getPayment('method') == "sms") {
        $transactionService = if_strlen2(
            $purchaseData->getPayment('sms_service'),
            $settings['sms_service']
        );
        $payment = new Payment($transactionService);
    } elseif ($purchaseData->getPayment('method') == "transfer") {
        $transactionService = if_strlen2(
            $purchaseData->getPayment('transfer_service'),
            $settings['transfer_service']
        );
        $payment = new Payment($transactionService);
    }

    // Pobieramy ile kosztuje ta usługa dla przelewu / portfela
    if ($purchaseData->getPayment('cost') === null) {
        $purchaseData->setPayment([
            'cost' => $purchaseData->getTariff()->getProvision(),
        ]);
    }

    // Metoda płatności
    if ($purchaseData->getPayment('method') == "wallet" && !is_logged()) {
        return [
            'status' => "wallet_not_logged",
            'text' => $lang->translate('no_login_no_wallet'),
            'positive' => false,
        ];
    }

    if ($purchaseData->getPayment('method') == "transfer") {
        if ($purchaseData->getPayment('cost') <= 1) {
            return [
                'status' => "too_little_for_transfer",
                'text' => $lang->sprintf(
                    $lang->translate('transfer_above_amount'),
                    $settings['currency']
                ),
                'positive' => false,
            ];
        }

        if (!$payment->getPaymentModule()->supportTransfer()) {
            return [
                'status' => "transfer_unavailable",
                'text' => $lang->translate('transfer_unavailable'),
                'positive' => false,
            ];
        }
    } elseif (
        $purchaseData->getPayment('method') == "sms" &&
        !$payment->getPaymentModule()->supportSms()
    ) {
        return [
            'status' => "sms_unavailable",
            'text' => $lang->translate('sms_unavailable'),
            'positive' => false,
        ];
    } elseif ($purchaseData->getPayment('method') == "sms" && $purchaseData->getTariff() === null) {
        return [
            'status' => "no_sms_option",
            'text' => $lang->translate('no_sms_payment'),
            'positive' => false,
        ];
    }

    // Kod SMS
    $purchaseData->setPayment([
        'sms_code' => trim($purchaseData->getPayment('sms_code')),
    ]);

    if (
        $purchaseData->getPayment('method') == "sms" &&
        ($warning = check_for_warnings("sms_code", $purchaseData->getPayment('sms_code')))
    ) {
        $warnings['sms_code'] = array_merge((array) $warnings['sms_code'], $warning);
    }

    // Kod na usługę
    if ($purchaseData->getPayment('method') == "service_code") {
        if (!strlen($purchaseData->getPayment('service_code'))) {
            $warnings['service_code'][] = $lang->translate('field_no_empty');
        }
    }

    if (!empty($warnings)) {
        $warningData = [];
        $warningData['warnings'] = format_warnings($warnings);

        return [
            'status' => "warnings",
            'text' => $lang->translate('form_wrong_filled'),
            'positive' => false,
            'data' => $warningData,
        ];
    }

    if ($purchaseData->getPayment('method') === "sms") {
        // Sprawdzamy kod zwrotny
        $result = $payment->paySms(
            $purchaseData->getPayment('sms_code'),
            $purchaseData->getTariff(),
            $purchaseData->user
        );
        $paymentId = $result['payment_id'];

        if ($result['status'] !== 'ok') {
            return [
                'status' => $result['status'],
                'text' => $result['text'],
                'positive' => false,
            ];
        }
    } elseif ($purchaseData->getPayment('method') === "wallet") {
        // Dodanie informacji o płatności z portfela
        $paymentId = pay_wallet($purchaseData->getPayment('cost'), $purchaseData->user);

        // Metoda pay_wallet zwróciła błąd.
        if (is_array($paymentId)) {
            return $paymentId;
        }
    } elseif ($purchaseData->getPayment('method') === "service_code") {
        // Dodanie informacji o płatności z portfela
        $paymentId = pay_service_code($purchaseData, $serviceModule);

        // Funkcja pay_service_code zwróciła błąd.
        if (is_array($paymentId)) {
            return $paymentId;
        }
    }

    if (in_array($purchaseData->getPayment('method'), ["wallet", "sms", "service_code"])) {
        // Dokonujemy zakupu usługi
        $purchaseData->setPayment([
            'payment_id' => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchaseData);

        return [
            'status' => "purchased",
            'text' => $lang->translate('purchase_success'),
            'positive' => true,
            'data' => ['bsid' => $boughtServiceId],
        ];
    }

    if ($purchaseData->getPayment('method') == "transfer") {
        $purchaseData->setDesc(
            $lang->sprintf($lang->translate('payment_for_service'), $serviceModule->service['name'])
        );

        return $payment->payTransfer($purchaseData);
    }
}

/**
 * @param User $userAdmin
 * @return int|string
 */
function pay_by_admin($userAdmin)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    // Dodawanie informacji o płatności
    $db->query(
        $db->prepare(
            "INSERT INTO `" .
                TABLE_PREFIX .
                "payment_admin` (`aid`, `ip`, `platform`) " .
                "VALUES ('%d', '%s', '%s')",
            [$userAdmin->getUid(), $userAdmin->getLastIp(), $userAdmin->getPlatform()]
        )
    );

    return $db->lastId();
}

/**
 * @param int  $cost
 * @param User $user
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
            'status' => "no_money",
            'text' => $lang->translate('not_enough_money'),
            'positive' => false,
        ];
    }

    // Zabieramy kasę z portfela
    charge_wallet($user->getUid(), -$cost);

    // Dodajemy informacje o płatności portfelem
    $db->query(
        $db->prepare(
            "INSERT INTO `" .
                TABLE_PREFIX .
                "payment_wallet` " .
                "SET `cost` = '%d', `ip` = '%s', `platform` = '%s'",
            [$cost, $user->getLastIp(), $user->getPlatform()]
        )
    );

    return $db->lastId();
}

/**
 * @param Purchase                                                   $purchaseData
 * @param Service|ServiceChargeWallet|ServiceExtraFlags|ServiceOther $serviceModule
 *
 * @return array|int|string
 */
function pay_service_code(Purchase $purchaseData, $serviceModule)
{
    /** @var TranslationManager $translationManager */
    $translationManager = app()->make(TranslationManager::class);
    $lang = $translationManager->user();
    $langShop = $translationManager->shop();

    /** @var Database $db */
    $db = app()->make(Database::class);

    $result = $db->query(
        $db->prepare(
            "SELECT * FROM `" .
                TABLE_PREFIX .
                "service_codes` " .
                "WHERE `code` = '%s' " .
                "AND `service` = '%s' " .
                "AND (`server` = '0' OR `server` = '%s') " .
                "AND (`tariff` = '0' OR `tariff` = '%d') " .
                "AND (`uid` = '0' OR `uid` = '%s')",
            [
                $purchaseData->getPayment('service_code'),
                $purchaseData->getService(),
                $purchaseData->getOrder('server'),
                $purchaseData->getTariff(),
                $purchaseData->user->getUid(),
            ]
        )
    );

    while ($row = $db->fetchArrayAssoc($result)) {
        if ($serviceModule->serviceCodeValidate($purchaseData, $row)) {
            // Znalezlismy odpowiedni kod
            $db->query(
                $db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "service_codes` " . "WHERE `id` = '%d'",
                    [$row['id']]
                )
            );

            // Dodajemy informacje o płatności kodem
            $db->query(
                $db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "payment_code` " .
                        "SET `code` = '%s', `ip` = '%s', `platform` = '%s'",
                    [
                        $purchaseData->getPayment('service_code'),
                        $purchaseData->user->getLastip(),
                        $purchaseData->user->getPlatform(),
                    ]
                )
            );
            $paymentId = $db->lastId();

            log_info(
                $langShop->sprintf(
                    $langShop->translate('purchase_code'),
                    $purchaseData->getPayment('service_code'),
                    $purchaseData->user->getUsername(),
                    $purchaseData->user->getUid(),
                    $paymentId
                )
            );

            return $paymentId;
        }
    }

    return [
        'status' => "wrong_service_code",
        'text' => $lang->translate('bad_service_code'),
        'positive' => false,
    ];
}

/**
 * Add information about purchasing a service
 *
 * @param integer $uid
 * @param string  $userName
 * @param string  $ip
 * @param string  $method
 * @param string  $paymentId
 * @param string  $service
 * @param integer $server
 * @param string  $amount
 * @param string  $authData
 * @param string  $email
 * @param array   $extraData
 *
 * @return int|string
 */
function add_bought_service_info(
    $uid,
    $userName,
    $ip,
    $method,
    $paymentId,
    $service,
    $server,
    $amount,
    $authData,
    $email,
    $extraData = []
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
    $db->query(
        $db->prepare(
            "INSERT INTO `" .
                TABLE_PREFIX .
                "bought_services` " .
                "SET `uid` = '%d', `payment` = '%s', `payment_id` = '%s', `service` = '%s', " .
                "`server` = '%d', `amount` = '%s', `auth_data` = '%s', `email` = '%s', `extra_data` = '%s'",
            [
                $uid,
                $method,
                $paymentId,
                $service,
                $server,
                $amount,
                $authData,
                $email,
                json_encode($extraData),
            ]
        )
    );
    $bougtServiceId = $db->lastId();

    $ret = $lang->translate('none');
    if (strlen($email)) {
        $message = purchase_info([
            'purchase_id' => $bougtServiceId,
            'action' => "email",
        ]);
        if (strlen($message)) {
            $title =
                $service == 'charge_wallet'
                    ? $lang->translate('charge_wallet')
                    : $lang->translate('purchase');
            $ret = $mailer->send($email, $authData, $title, $message);
        }

        if ($ret == "not_sent") {
            $ret = "nie wysłano";
        } else {
            if ($ret == "sent") {
                $ret = "wysłano";
            }
        }
    }

    $tempService = $heart->getService($service);
    $tempServer = $heart->getServer($server);
    $amount = $amount != -1 ? "{$amount} {$tempService['tag']}" : $lang->translate('forever');
    log_info(
        $langShop->sprintf(
            $langShop->translate('bought_service_info'),
            $service,
            $authData,
            $amount,
            $tempServer['name'],
            $paymentId,
            $ret,
            $userName,
            $uid,
            $ip
        )
    );
    unset($tempServer);

    return $bougtServiceId;
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
    }
    // Wyszukujemy po id płatności
    else {
        if (isset($data['payment']) && isset($data['payment_id'])) {
            $where = $db->prepare("t.payment = '%s' AND t.payment_id = '%s'", [
                $data['payment'],
                $data['payment_id'],
            ]);
        } else {
            return "";
        }
    }

    $pbs = $db->fetchArrayAssoc(
        $db->query("SELECT * FROM ({$settings['transactions_query']}) as t " . "WHERE {$where}")
    );

    // Brak wynikow
    if (empty($pbs)) {
        return "Brak zakupu w bazie.";
    }

    $serviceModule = $heart->getServiceModule($pbs['service']);

    return $serviceModule !== null && $serviceModule instanceof IServicePurchaseWeb
        ? $serviceModule->purchaseInfo($data['action'], $pbs)
        : "";
}

/**
 * Pozyskuje z bazy wszystkie usługi użytkowników
 *
 * @param string|int $conditions Jezeli jest tylko jeden element w tablicy, to zwroci ten element zamiast tablicy
 * @param bool       $takeOut
 *
 * @return array
 */
function get_users_services($conditions = '', $takeOut = true)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    /** @var Heart $heart */
    $heart = app()->make(Heart::class);

    if (my_is_integer($conditions)) {
        $conditions = "WHERE `id` = " . intval($conditions);
    }

    $output = $usedTable = [];
    // Niestety dla każdego modułu musimy wykonać osobne zapytanie :-(
    foreach ($heart->getServicesModules() as $serviceModuleData) {
        $table = $serviceModuleData['classsimple']::USER_SERVICE_TABLE;
        if (!strlen($table) || array_key_exists($table, $usedTable)) {
            continue;
        }

        $result = $db->query(
            "SELECT us.*, m.*, UNIX_TIMESTAMP() AS `now` FROM `" .
                TABLE_PREFIX .
                "user_service` AS us " .
                "INNER JOIN `" .
                TABLE_PREFIX .
                $table .
                "` AS m ON m.us_id = us.id " .
                $conditions .
                " ORDER BY us.id DESC "
        );

        while ($row = $db->fetchArrayAssoc($result)) {
            unset($row['us_id']);
            $output[$row['id']] = $row;
        }

        $usedTable[$table] = true;
    }

    ksort($output);
    $output = array_reverse($output);

    return $takeOut && count($output) == 1 ? $output[0] : $output;
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

    $deleteIds = $usersServices = [];
    foreach (
        get_users_services("WHERE `expire` != '-1' AND `expire` < UNIX_TIMESTAMP()")
        as $userService
    ) {
        if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
            continue;
        }

        if ($serviceModule->userServiceDelete($userService, 'task')) {
            $deleteIds[] = $userService['id'];
            $usersServices[] = $userService;

            $userServiceDesc = '';
            foreach ($userService as $key => $value) {
                if (strlen($userServiceDesc)) {
                    $userServiceDesc .= ' ; ';
                }

                $userServiceDesc .= ucfirst(strtolower($key)) . ': ' . $value;
            }

            log_info(
                $langShop->sprintf($langShop->translate('expired_service_delete'), $userServiceDesc)
            );
        }
    }

    // Usuwamy usugi ktre zwróciły true
    if (!empty($deleteIds)) {
        $db->query(
            "DELETE FROM `" .
                TABLE_PREFIX .
                "user_service` " .
                "WHERE `id` IN (" .
                implode(", ", $deleteIds) .
                ")"
        );
    }

    // Wywołujemy akcje po usunieciu
    foreach ($usersServices as $userService) {
        if (($serviceModule = $heart->getServiceModule($userService['service'])) === null) {
            continue;
        }

        $serviceModule->userServiceDeletePost($userService);
    }
}

function log_info($string)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $db->query(
        $db->prepare("INSERT INTO `" . TABLE_PREFIX . "logs` " . "SET `text` = '%s'", [$string])
    );
}

function create_dom_element($name, $text = "", $data = [])
{
    $features = "";
    foreach ($data as $key => $value) {
        if (is_array($value) || !strlen($value)) {
            continue;
        }

        $features .=
            (strlen($features) ? " " : "") . $key . '="' . str_replace('"', '\"', $value) . '"';
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

    $nameHsafe = htmlspecialchars($name);
    $output = "<{$nameHsafe} {$features}>";
    if (strlen($text)) {
        $output .= $text;
    }

    if (!in_array($name, ["input", "img"])) {
        $output .= "</{$nameHsafe}>";
    }

    return $output;
}

function create_brick($text, $class = "", $alpha = 0.2)
{
    $brickR = rand(0, 255);
    $brickG = rand(0, 255);
    $brickB = rand(0, 255);

    return create_dom_element("div", $text, [
        'class' => "notification" . ($class ? " {$class}" : ""),
        'style' => [
            'border-color' => "rgb({$brickR},{$brickG},{$brickB})",
            'background-color' => "rgba({$brickR},{$brickG},{$brickB},{$alpha})",
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
        $cfIpRanges = [
            "103.21.244.0/22",
            "103.22.200.0/22",
            "103.31.4.0/22",
            "104.16.0.0/12",
            "108.162.192.0/18",
            "131.0.72.0/22",
            "141.101.64.0/18",
            "162.158.0.0/15",
            "172.64.0.0/13",
            "173.245.48.0/20",
            "188.114.96.0/20",
            "190.93.240.0/20",
            "197.234.240.0/22",
            "198.41.128.0/17",
        ];

        foreach ($cfIpRanges as $range) {
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
    $finalRand = "";
    for ($i = 0; $i < $length; $i++) {
        $finalRand .= $chars[rand(0, strlen($chars) - 1)];
    }

    return $finalRand;
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

    return $dtF
        ->diff($dtT)
        ->format(
            "%a {$lang->translate('days')} {$lang->translate('and')} %h {$lang->translate('hours')}"
        );
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

function searchWhere($searchIds, $search, &$where)
{
    /** @var Database $db */
    $db = app()->make(Database::class);

    $searchWhere = [];
    $searchLike = $db->escape('%' . implode('%', mb_str_split($search)) . '%');

    foreach ($searchIds as $searchId) {
        $searchWhere[] = "{$searchId} LIKE '{$searchLike}'";
    }

    if (!empty($searchWhere)) {
        $searchWhere = implode(" OR ", $searchWhere);
        if (strlen($where)) {
            $where .= " AND ";
        }

        $where .= "( {$searchWhere} )";
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
            $netmaskDec = ip2long($netmask);

            return (ip2long($ip) & $netmaskDec) == (ip2long($range) & $netmaskDec);
        } else {
            // $netmask is a CIDR size block
            // fix the range argument
            $x = explode('.', $range);
            while (count($x) < 4) {
                $x[] = '0';
            }
            list($a, $b, $c, $d) = $x;
            $range = sprintf(
                "%u.%u.%u.%u",
                empty($a) ? '0' : $a,
                empty($b) ? '0' : $b,
                empty($c) ? '0' : $c,
                empty($d) ? '0' : $d
            );
            $rangeDec = ip2long($range);
            $ipDec = ip2long($ip);

            # Strategy 1 - Create the netmask with 'netmask' 1s and then fill it to 32 with 0s
            #$netmaskDec = bindec(str_pad('', $netmask, '1') . str_pad('', 32-$netmask, '0'));

            # Strategy 2 - Use math to create it
            $wildcardDec = pow(2, 32 - $netmask) - 1;
            $netmaskDec = ~$wildcardDec;

            return ($ipDec & $netmaskDec) == ($rangeDec & $netmaskDec);
        }
    } else {
        // range might be 255.255.*.* or 1.2.3.0-1.2.3.255
        if (strpos($range, '*') !== false) {
            // a.b.*.* format
            // Just convert to A-B format by setting * to 0 for A and 255 for B
            $lower = str_replace('*', '0', $range);
            $upper = str_replace('*', '255', $range);
            $range = "$lower-$upper";
        }

        if (strpos($range, '-') !== false) {
            // A-B format
            list($lower, $upper) = explode('-', $range, 2);
            $lowerDec = (float) sprintf("%u", ip2long($lower));
            $upperDec = (float) sprintf("%u", ip2long($upper));
            $ipDec = (float) sprintf("%u", ip2long($ip));

            return $ipDec >= $lowerDec && $ipDec <= $upperDec;
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
    return substr($haystack, 0, strlen($needle)) === (string) $needle;
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

function log_error($message)
{
    log_to_file(app()->errorsLogPath(), $message);
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
