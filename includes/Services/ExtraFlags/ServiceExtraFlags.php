<?php
namespace App\Services\ExtraFlags;

use App\Exceptions\UnauthorizedException;
use App\System\Auth;
use App\System\Heart;
use App\Models\Purchase;
use App\Services\Interfaces\IServiceActionExecute;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseOutside;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceServiceCode;
use App\Services\Interfaces\IServiceServiceCodeAdminManage;
use App\Services\Interfaces\IServiceTakeOver;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserOwnServicesEdit;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\Services\Interfaces\IServiceUserServiceAdminEdit;

class ServiceExtraFlags extends ServiceExtraFlagsSimple implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServicePurchaseOutside,
    IServiceUserServiceAdminAdd,
    IServiceUserServiceAdminEdit,
    IServiceActionExecute,
    IServiceUserOwnServices,
    IServiceUserOwnServicesEdit,
    IServiceTakeOver,
    IServiceServiceCode,
    IServiceServiceCodeAdminManage
{
    /** @var Heart */
    private $heart;

    /** @var Auth */
    private $auth;

    public function __construct($service = null)
    {
        parent::__construct($service);

        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);

        $this->service['flags_hsafe'] = htmlspecialchars($this->service['flags']);
    }

    public function purchaseFormGet()
    {
        $heart = $this->heart;
        $user = $this->auth->user();

        // Generujemy typy usługi
        $types = "";
        for ($i = 0, $value = 1; $i < 3; $value = 1 << ++$i) {
            if ($this->service['types'] & $value) {
                $type = ExtraFlagType::getTypeName($value);
                $types .= $this->template->render(
                    "services/extra_flags/service_type",
                    compact('value', 'type')
                );
            }
        }

        // Pobieranie serwerów na których można zakupić daną usługę
        $servers = "";
        foreach ($heart->getServers() as $id => $row) {
            // Usługi nie mozna kupic na tym serwerze
            if (!$heart->serverServiceLinked($id, $this->service['id'])) {
                continue;
            }

            $servers .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
            ]);
        }

        return $this->template->render(
            "services/extra_flags/purchase_form",
            compact('types', 'user', 'servers') + ['serviceId' => $this->service['id']]
        );
    }

    public function purchaseFormValidate($data)
    {
        // Wyłuskujemy taryfę
        $value = explode(';', $data['value']);

        // Pobieramy auth_data
        $authData = $this->getAuthData($data);

        $purchaseData = new Purchase();
        $purchaseData->setOrder([
            'server' => $data['server'],
            'type' => $data['type'],
            'auth_data' => trim($authData),
            'password' => $data['password'],
            'passwordr' => $data['password_repeat'],
        ]);
        $purchaseData->setTariff($this->heart->getTariff($value[2]));
        $purchaseData->setEmail($data['email']);

        return $this->purchaseDataValidate($purchaseData);
    }

    /**
     * @param Purchase $purchaseData
     * @return array
     */
    public function purchaseDataValidate(Purchase $purchaseData)
    {
        $warnings = [];

        // Serwer
        if (!strlen($purchaseData->getOrder('server'))) {
            $warnings['server'][] = $this->lang->translate('must_choose_server');
        } else {
            // Sprawdzanie czy serwer o danym id istnieje w bazie
            $server = $this->heart->getServer($purchaseData->getOrder('server'));

            if (!$this->heart->serverServiceLinked($server['id'], $this->service['id'])) {
                $warnings['server'][] = $this->lang->translate('chosen_incorrect_server');
            }
        }

        // Wartość usługi
        if (!$purchaseData->getTariff()) {
            $warnings['value'][] = $this->lang->translate('must_choose_amount');
        } else {
            // Wyszukiwanie usługi o konkretnej cenie
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" .
                        TABLE_PREFIX .
                        "pricelist` " .
                        "WHERE `service` = '%s' AND `tariff` = '%d' AND ( `server` = '%d' OR `server` = '-1' )",
                    [$this->service['id'], $purchaseData->getTariff(), $server['id']]
                )
            );

            if (!$this->db->numRows($result)) {
                // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
                return [
                    'status' => "no_option",
                    'text' => $this->lang->translate('service_not_affordable'),
                    'positive' => false,
                ];
            }

            $price = $this->db->fetchArrayAssoc($result);
        }

        // Typ usługi
        // Mogą być tylko 3 rodzaje typu
        if (
            $purchaseData->getOrder('type') != ExtraFlagType::TYPE_NICK &&
            $purchaseData->getOrder('type') != ExtraFlagType::TYPE_IP &&
            $purchaseData->getOrder('type') != ExtraFlagType::TYPE_SID
        ) {
            $warnings['type'][] = $this->lang->translate('must_choose_type');
        } else {
            if (!($this->service['types'] & $purchaseData->getOrder('type'))) {
                $warnings['type'][] = $this->lang->translate('chosen_incorrect_type');
            } else {
                if (
                    $purchaseData->getOrder('type') &
                    (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)
                ) {
                    // Nick
                    if ($purchaseData->getOrder('type') == ExtraFlagType::TYPE_NICK) {
                        if (
                            $warning = check_for_warnings(
                                "nick",
                                $purchaseData->getOrder('auth_data')
                            )
                        ) {
                            $warnings['nick'] = array_merge((array) $warnings['nick'], $warning);
                        }

                        // Sprawdzanie czy istnieje już taka usługa
                        $query = $this->db->prepare(
                            "SELECT `password` FROM `" .
                                TABLE_PREFIX .
                                $this::USER_SERVICE_TABLE .
                                "` " .
                                "WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
                            [
                                ExtraFlagType::TYPE_NICK,
                                $purchaseData->getOrder('auth_data'),
                                $server['id'],
                            ]
                        );
                    }
                    // IP
                    else {
                        if ($purchaseData->getOrder('type') == ExtraFlagType::TYPE_IP) {
                            if (
                                $warning = check_for_warnings(
                                    "ip",
                                    $purchaseData->getOrder('auth_data')
                                )
                            ) {
                                $warnings['ip'] = array_merge((array) $warnings['ip'], $warning);
                            }

                            // Sprawdzanie czy istnieje już taka usługa
                            $query = $this->db->prepare(
                                "SELECT `password` FROM `" .
                                    TABLE_PREFIX .
                                    $this::USER_SERVICE_TABLE .
                                    "` " .
                                    "WHERE `type` = '%d' AND `auth_data` = '%s' AND `server` = '%d'",
                                [
                                    ExtraFlagType::TYPE_IP,
                                    $purchaseData->getOrder('auth_data'),
                                    $server['id'],
                                ]
                            );
                        }
                    }

                    // Hasło
                    if (
                        $warning = check_for_warnings(
                            "password",
                            $purchaseData->getOrder('password')
                        )
                    ) {
                        $warnings['password'] = array_merge(
                            (array) $warnings['password'],
                            $warning
                        );
                    }
                    if (
                        $purchaseData->getOrder('password') != $purchaseData->getOrder('passwordr')
                    ) {
                        $warnings['password_repeat'][] = $this->lang->translate(
                            'passwords_not_match'
                        );
                    }

                    // Sprawdzanie czy istnieje już taka usługa
                    if ($tmpPassword = $this->db->getColumn($query, 'password')) {
                        // TODO: Usunąć md5 w przyszłości
                        if (
                            $tmpPassword != $purchaseData->getOrder('password') &&
                            $tmpPassword != md5($purchaseData->getOrder('password'))
                        ) {
                            $warnings['password'][] = $this->lang->translate(
                                'existing_service_has_different_password'
                            );
                        }
                    }

                    unset($tmpPassword);
                }
                // SteamID
                else {
                    if (
                        $warning = check_for_warnings("sid", $purchaseData->getOrder('auth_data'))
                    ) {
                        $warnings['sid'] = array_merge((array) $warnings['sid'], $warning);
                    }
                }
            }
        }

        // E-mail
        if (
            (strpos($purchaseData->user->getPlatform(), "engine") !== 0 ||
                strlen($purchaseData->getEmail())) &&
            ($warning = check_for_warnings("email", $purchaseData->getEmail()))
        ) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchaseData->setOrder([
            'amount' => $price['amount'],
            'forever' => $price['amount'] == -1 ? true : false,
        ]);

        if (strlen($server['sms_service'])) {
            $purchaseData->setPayment([
                'sms_service' => $server['sms_service'],
            ]);
        }

        return [
            'status' => "ok",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    public function orderDetails(Purchase $purchaseData)
    {
        $server = $this->heart->getServer($purchaseData->getOrder('server'));
        $typeName = $this->getTypeName2($purchaseData->getOrder('type'));
        if (strlen($purchaseData->getOrder('password'))) {
            $password =
                "<strong>{$this->lang->translate('password')}</strong>: " .
                htmlspecialchars($purchaseData->getOrder('password')) .
                "<br />";
        }
        $email = strlen($purchaseData->getEmail())
            ? htmlspecialchars($purchaseData->getEmail())
            : $this->lang->translate('none');
        $authData = htmlspecialchars($purchaseData->getOrder('auth_data'));
        $amount = !$purchaseData->getOrder('forever')
            ? $purchaseData->getOrder('amount') . " " . $this->service['tag']
            : $this->lang->translate('forever');

        return $this->template->render(
            "services/extra_flags/order_details",
            compact('server', 'amount', 'typeName', 'authData', 'password', 'email') + [
                'serviceName' => $this->service['name'],
            ],
            true,
            false
        );
    }

    public function purchase(Purchase $purchaseData)
    {
        $this->addPlayerFlags(
            $purchaseData->user->getUid(),
            $purchaseData->getOrder('type'),
            $purchaseData->getOrder('auth_data'),
            $purchaseData->getOrder('password'),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('server'),
            $purchaseData->getOrder('forever')
        );

        return add_bought_service_info(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service['id'],
            $purchaseData->getOrder('server'),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('auth_data'),
            $purchaseData->getEmail(),
            [
                'type' => $purchaseData->getOrder('type'),
                'password' => $purchaseData->getOrder('password'),
            ]
        );
    }

    private function addPlayerFlags(
        $uid,
        $type,
        $authData,
        $password,
        $days,
        $serverId,
        $forever = false
    ) {
        $authData = trim($authData);

        // Usunięcie przestarzałych usług gracza
        delete_users_old_services();

        // Usunięcie przestarzałych flag graczy
        // Tak jakby co
        $this->deleteOldFlags();

        // Dodajemy usługę gracza do listy usług
        // Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `us_id` FROM `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "WHERE `service` = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                [$this->service['id'], $serverId, $type, $authData]
            )
        );

        if ($this->db->numRows($result)) {
            // Aktualizujemy
            $row = $this->db->fetchArrayAssoc($result);
            $userServiceId = $row['us_id'];

            $this->updateUserService(
                [
                    [
                        'column' => 'uid',
                        'value' => "'%d'",
                        'data' => [$uid],
                    ],
                    [
                        'column' => 'password',
                        'value' => "'%s'",
                        'data' => [$password],
                    ],
                    [
                        'column' => 'expire',
                        'value' => "IF('%d' = '1', -1, `expire` + '%d')",
                        'data' => [$forever, $days * 24 * 60 * 60],
                    ],
                ],
                $userServiceId,
                $userServiceId
            );
        } else {
            // Wstawiamy
            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "user_service` (`uid`, `service`, `expire`) " .
                        "VALUES ('%d', '%s', IF('%d' = '1', '-1', UNIX_TIMESTAMP() + '%d')) ",
                    [$uid, $this->service['id'], $forever, $days * 24 * 60 * 60]
                )
            );
            $userServiceId = $this->db->lastId();

            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` (`us_id`, `server`, `service`, `type`, `auth_data`, `password`) " .
                        "VALUES ('%d', '%d', '%s', '%d', '%s', '%s')",
                    [$userServiceId, $serverId, $this->service['id'], $type, $authData, $password]
                )
            );
        }

        // Ustawiamy jednakowe hasła dla wszystkich usług tego gracza na tym serwerze
        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "SET `password` = '%s' " .
                    "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                [$password, $serverId, $type, $authData]
            )
        );

        // Przeliczamy flagi gracza, ponieważ dodaliśmy nową usługę
        $this->recalculatePlayerFlags($serverId, $type, $authData);
    }

    private function deleteOldFlags()
    {
        $this->db->query(
            "DELETE FROM `" .
                TABLE_PREFIX .
                "players_flags` " .
                "WHERE (`a` < UNIX_TIMESTAMP() AND `a` != '-1') " .
                "AND (`b` < UNIX_TIMESTAMP() AND `b` != '-1') " .
                "AND (`c` < UNIX_TIMESTAMP() AND `c` != '-1') " .
                "AND (`d` < UNIX_TIMESTAMP() AND `d` != '-1') " .
                "AND (`e` < UNIX_TIMESTAMP() AND `e` != '-1') " .
                "AND (`f` < UNIX_TIMESTAMP() AND `f` != '-1') " .
                "AND (`g` < UNIX_TIMESTAMP() AND `g` != '-1') " .
                "AND (`h` < UNIX_TIMESTAMP() AND `h` != '-1') " .
                "AND (`i` < UNIX_TIMESTAMP() AND `i` != '-1') " .
                "AND (`j` < UNIX_TIMESTAMP() AND `j` != '-1') " .
                "AND (`k` < UNIX_TIMESTAMP() AND `k` != '-1') " .
                "AND (`l` < UNIX_TIMESTAMP() AND `l` != '-1') " .
                "AND (`m` < UNIX_TIMESTAMP() AND `m` != '-1') " .
                "AND (`n` < UNIX_TIMESTAMP() AND `n` != '-1') " .
                "AND (`o` < UNIX_TIMESTAMP() AND `o` != '-1') " .
                "AND (`p` < UNIX_TIMESTAMP() AND `p` != '-1') " .
                "AND (`q` < UNIX_TIMESTAMP() AND `q` != '-1') " .
                "AND (`r` < UNIX_TIMESTAMP() AND `r` != '-1') " .
                "AND (`s` < UNIX_TIMESTAMP() AND `s` != '-1') " .
                "AND (`t` < UNIX_TIMESTAMP() AND `t` != '-1') " .
                "AND (`u` < UNIX_TIMESTAMP() AND `u` != '-1') " .
                "AND (`v` < UNIX_TIMESTAMP() AND `v` != '-1') " .
                "AND (`w` < UNIX_TIMESTAMP() AND `w` != '-1') " .
                "AND (`x` < UNIX_TIMESTAMP() AND `x` != '-1') " .
                "AND (`y` < UNIX_TIMESTAMP() AND `y` != '-1') " .
                "AND (`z` < UNIX_TIMESTAMP() AND `z` != '-1')"
        );
    }

    private function recalculatePlayerFlags($serverId, $type, $authData)
    {
        // Musi byc podany typ, bo inaczej nam wywali wszystkie usługi bez typu
        // Bez serwera oraz auth_data, skrypt po prostu nic nie zrobi
        if (!$type) {
            return;
        }

        // Usuwanie danych z bazy players_flags
        // Ponieważ za chwilę będziemy je tworzyć na nowo
        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" .
                    TABLE_PREFIX .
                    "players_flags` " .
                    "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                [$serverId, $type, $authData]
            )
        );

        // Pobieranie wszystkich usług na konkretne dane
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `expire` > UNIX_TIMESTAMP() OR `expire` = -1 )",
                [$serverId, $type, $authData]
            )
        );

        // Wyliczanie za jaki czas dana flaga ma wygasnąć
        $flags = [];
        $password = "";
        while ($row = $this->db->fetchArrayAssoc($result)) {
            // Pobranie hasła, bierzemy je tylko raz na początku
            $password = $password ? $password : $row['password'];

            $service = $this->heart->getService($row['service']);
            for ($i = 0; $i < strlen($service['flags']); ++$i) {
                // Bierzemy maksa, ponieważ inaczej robią się problemy.
                // A tak to jak wygaśnie jakaś usługa, to wykona się cron, usunie ją i przeliczy flagi jeszcze raz
                // I znowu weźmie maksa
                // Czyli stan w tabeli players flags nie jest do końca odzwierciedleniem rzeczywistości :)
                $flags[$service['flags'][$i]] = $this->maxMinus(
                    array_get($flags, $service['flags'][$i]),
                    $row['expire']
                );
            }
        }

        // Formowanie flag do zapytania
        $set = '';
        foreach ($flags as $flag => $amount) {
            $set .= $this->db->prepare(", `%s` = '%d'", [$flag, $amount]);
        }

        // Dodanie flag
        if (strlen($set)) {
            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
                        TABLE_PREFIX .
                        "players_flags` " .
                        "SET `server` = '%d', `type` = '%d', `auth_data` = '%s', `password` = '%s'{$set}",
                    [$serverId, $type, $authData, $password]
                )
            );
        }
    }

    public function purchaseInfo($action, $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $data['extra_data']['type_name'] = $this->getTypeName2($data['extra_data']['type']);
        if (strlen($data['extra_data']['password'])) {
            $password =
                "<strong>{$this->lang->translate('password')}</strong>: " .
                htmlspecialchars($data['extra_data']['password']) .
                "<br />";
        }
        $amount =
            $data['amount'] != -1
                ? "{$data['amount']} {$this->service['tag']}"
                : $this->lang->translate('forever');
        $data['auth_data'] = htmlspecialchars($data['auth_data']);
        $data['extra_data']['password'] = htmlspecialchars($data['extra_data']['password']);
        $data['email'] = htmlspecialchars($data['email']);
        $cost = $data['cost']
            ? number_format($data['cost'] / 100.0, 2) . " " . $this->settings['currency']
            : $this->lang->translate('none');
        $data['income'] = number_format($data['income'] / 100.0, 2);

        if ($data['payment'] == "sms") {
            $data['sms_code'] = htmlspecialchars($data['sms_code']);
            $data['sms_text'] = htmlspecialchars($data['sms_text']);
            $data['sms_number'] = htmlspecialchars($data['sms_number']);
        }

        $server = $this->heart->getServer($data['server']);

        if ($data['extra_data']['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)) {
            $setinfo = $this->lang->sprintf(
                $this->lang->translate('type_setinfo'),
                htmlspecialchars($data['extra_data']['password'])
            );
        }

        if ($action == "email") {
            return $this->template->render(
                "services/extra_flags/purchase_info_email",
                compact('data', 'amount', 'server', 'password', 'setinfo') + [
                    'serviceName' => $this->service['name'],
                ],
                true,
                false
            );
        }

        if ($action == "web") {
            return $this->template->render(
                "services/extra_flags/purchase_info_web",
                compact('cost', 'server', 'amount', 'data', 'password', 'setinfo') + [
                    'serviceName' => $this->service['name'],
                ],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => ($output = $this->lang->sprintf(
                    $this->lang->translate('service_was_bought'),
                    $this->service['name'],
                    $server['name']
                )),
                'class' => "outcome",
            ];
        }

        return '';
    }

    // ----------------------------------------------------------------------------------
    // ### Zarządzanie usługami użytkowników przez admina

    public function userServiceAdminAddFormGet()
    {
        // Pobieramy listę typów usługi, (1<<2) ostatni typ
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            if ($this->service['types'] & $optionId) {
                $types .= create_dom_element("option", $this->getTypeName($optionId), [
                    'value' => $optionId,
                ]);
            }
        }

        // Pobieramy listę serwerów
        $servers = "";
        foreach ($this->heart->getServers() as $id => $row) {
            if (!$this->heart->serverServiceLinked($id, $this->service['id'])) {
                continue;
            }

            $servers .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
            ]);
        }

        return $this->template->render(
            "services/extra_flags/user_service_admin_add",
            compact('types', 'servers') + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    //
    // Funkcja dodawania usługi przez PA
    //
    public function userServiceAdminAdd($data)
    {
        $user = $this->auth->user();

        $warnings = [];

        // Pobieramy auth_data
        $data['auth_data'] = $this->getAuthData($data);

        // Sprawdzamy hasło, jeżeli podano nick albo ip
        if (
            $data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            ($warning = check_for_warnings("password", $data['password']))
        ) {
            $warnings['password'] = array_merge((array) $warnings['password'], $warning);
        }

        // Amount
        if (!$data['forever']) {
            if ($warning = check_for_warnings("number", $data['amount'])) {
                $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
            } else {
                if ($data['amount'] < 0) {
                    $warnings['amount'][] = $this->lang->translate('days_quantity_positive');
                }
            }
        }

        // E-mail
        if (strlen($data['email']) && ($warning = check_for_warnings("email", $data['email']))) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($data, $warnings);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Dodajemy usługę

        // Dodawanie informacji o płatności
        $paymentId = pay_by_admin($user);

        $purchaseData = new Purchase();
        $purchaseData->setService($this->service['id']);
        $purchaseData->user = $this->heart->getUser($data['uid']); // Pobieramy dane o użytkowniku na które jego wykupiona usługa
        $purchaseData->setPayment([
            'method' => "admin",
            'payment_id' => $paymentId,
        ]);
        $purchaseData->setOrder([
            'server' => $data['server'],
            'type' => $data['type'],
            'auth_data' => trim($data['auth_data']),
            'password' => $data['password'],
            'amount' => $data['amount'],
            'forever' => (bool) $data['forever'],
        ]);
        $purchaseData->setEmail($data['email']);
        $boughtServiceId = $this->purchase($purchaseData);

        log_info(
            $this->langShop->sprintf(
                $this->langShop->translate('admin_added_user_service'),
                $user->getUsername(),
                $user->getUid(),
                $boughtServiceId
            )
        );

        return [
            'status' => "ok",
            'text' => $this->lang->translate('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function userServiceAdminEditFormGet($userService)
    {
        // Pobranie usług
        $services = "";
        foreach ($this->heart->getServices() as $id => $row) {
            if (($serviceModule = $this->heart->getServiceModuleS($row['module'])) === null) {
                continue;
            }

            // Usługę możemy zmienić tylko na taka, która korzysta z tego samego modułu.
            // Inaczej to nie ma sensu, lepiej ją usunąć i dodać nową
            if ($this->getModuleId() != $serviceModule->getModuleId()) {
                continue;
            }

            $services .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
                'selected' => $userService['service'] == $row['id'] ? "selected" : "",
            ]);
        }

        // Dodajemy typ uslugi, (1<<2) ostatni typ
        $types = "";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            if ($this->service['types'] & $optionId) {
                $types .= create_dom_element("option", $this->getTypeName($optionId), [
                    'value' => $optionId,
                    'selected' => $optionId == $userService['type'] ? "selected" : "",
                ]);
            }
        }

        if ($userService['type'] == ExtraFlagType::TYPE_NICK) {
            $nick = htmlspecialchars($userService['auth_data']);
            $styles['nick'] = $styles['password'] = "display: table-row-group";
        } else {
            if ($userService['type'] == ExtraFlagType::TYPE_IP) {
                $ip = htmlspecialchars($userService['auth_data']);
                $styles['ip'] = $styles['password'] = "display: table-row-group";
            } else {
                if ($userService['type'] == ExtraFlagType::TYPE_SID) {
                    $sid = htmlspecialchars($userService['auth_data']);
                    $styles['sid'] = "display: table-row-group";
                }
            }
        }

        // Pobranie serwerów
        $servers = "";
        foreach ($this->heart->getServers() as $id => $row) {
            if (!$this->heart->serverServiceLinked($id, $this->service['id'])) {
                continue;
            }

            $servers .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
                'selected' => $userService['server'] == $row['id'] ? "selected" : "",
            ]);
        }

        // Pobranie hasła
        if (strlen($userService['password'])) {
            $password = "********";
        }

        // Zamiana daty
        if ($userService['expire'] == -1) {
            $checked = "checked";
            $disabled = "disabled";
            $userService['expire'] = "";
        } else {
            $userService['expire'] = date($this->settings['date_format'], $userService['expire']);
        }

        return $this->template->render(
            "services/extra_flags/user_service_admin_edit",
            compact(
                'userService',
                'types',
                'styles',
                'nick',
                'ip',
                'sid',
                'password',
                'services',
                'servers',
                'disabled',
                'checked'
            ) + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    //
    // Funkcja edytowania usługi przez admina z PA
    //
    public function userServiceAdminEdit($data, $userService)
    {
        $user = $this->auth->user();

        // Pobieramy auth_data
        $data['auth_data'] = $this->getAuthData($data);

        // Expire
        if (!$data['forever'] && ($data['expire'] = strtotime($data['expire'])) === false) {
            $warnings['expire'][] = $this->lang->translate('wrong_date_format');
        }
        // Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
        if (
            !strlen($data['password']) &&
            $data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            !strlen($userService['password'])
        ) {
            $warnings['password'][] = $this->lang->translate('field_no_empty');
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($data, $warnings);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Aktualizujemy usługę
        $editReturn = $this->userServiceEdit($userService, $data);

        if ($editReturn['status'] == 'ok') {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('admin_edited_user_service'),
                    $user->getUsername(),
                    $user->getUid(),
                    $userService['id']
                )
            );
        }

        return $editReturn;
    }

    //
    // Weryfikacja danych przy dodawaniu i przy edycji usługi gracza
    // Zebrane w jednej funkcji, aby nie mnożyć kodu
    //
    private function verifyUserServiceData($data, $warnings, $server = true)
    {
        // ID użytkownika
        if ($data['uid']) {
            if ($warning = check_for_warnings("uid", $data['uid'])) {
                $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
            } else {
                $editedUser = $this->heart->getUser($data['uid']);
                if (!$editedUser->exists()) {
                    $warnings['uid'][] = $this->lang->translate('no_account_id');
                }
            }
        }

        // Typ usługi
        // Mogą być tylko 3 rodzaje typu
        if (
            $data['type'] != ExtraFlagType::TYPE_NICK &&
            $data['type'] != ExtraFlagType::TYPE_IP &&
            $data['type'] != ExtraFlagType::TYPE_SID
        ) {
            $warnings['type'][] = $this->lang->translate('must_choose_service_type');
        } else {
            if (!($this->service['types'] & $data['type'])) {
                $warnings['type'][] = $this->lang->translate('forbidden_purchase_type');
            } else {
                if ($data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)) {
                    // Nick
                    if (
                        $data['type'] == ExtraFlagType::TYPE_NICK &&
                        ($warning = check_for_warnings("nick", $data['auth_data']))
                    ) {
                        $warnings['nick'] = array_merge((array) $warnings['nick'], $warning);
                    }
                    // IP
                    else {
                        if (
                            $data['type'] == ExtraFlagType::TYPE_IP &&
                            ($warning = check_for_warnings("ip", $data['auth_data']))
                        ) {
                            $warnings['ip'] = array_merge((array) $warnings['ip'], $warning);
                        }
                    }

                    // Hasło
                    if (
                        strlen($data['password']) &&
                        ($warning = check_for_warnings("password", $data['password']))
                    ) {
                        $warnings['password'] = array_merge(
                            (array) $warnings['password'],
                            $warning
                        );
                    }
                }
                // SteamID
                else {
                    if ($warning = check_for_warnings("sid", $data['auth_data'])) {
                        $warnings['sid'] = array_merge((array) $warnings['sid'], $warning);
                    }
                }
            }
        }

        // Server
        if ($server) {
            if (!strlen($data['server'])) {
                $warnings['server'][] = $this->lang->translate('choose_server_for_service');
            }
            // Wyszukiwanie serwera o danym id
            elseif (($server = $this->heart->getServer($data['server'])) === null) {
                $warnings['server'][] = $this->lang->translate('no_server_id');
            }
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }
    }

    public function userServiceDeletePost($userService)
    {
        // Odśwież flagi gracza
        $this->recalculatePlayerFlags(
            $userService['server'],
            $userService['type'],
            $userService['auth_data']
        );
    }

    // ----------------------------------------------------------------------------------
    // ### Edytowanie usług przez użytkownika

    public function userOwnServiceEditFormGet($userService)
    {
        // Dodajemy typ uslugi, (1<<2) ostatni typ
        $serviceInfo = [];
        $styles['nick'] = $styles['ip'] = $styles['sid'] = $styles['password'] = "display: none";
        for ($i = 0, $optionId = 1; $i < 3; $optionId = 1 << ++$i) {
            // Kiedy dana usługa nie wspiera danego typu i wykupiona usługa nie ma tego typu
            if (!($this->service['types'] & $optionId) && $optionId != $userService['type']) {
                continue;
            }

            $serviceInfo['types'] .= create_dom_element("option", $this->getTypeName($optionId), [
                'value' => $optionId,
                'selected' => $optionId == $userService['type'] ? "selected" : "",
            ]);

            if ($optionId == $userService['type']) {
                switch ($optionId) {
                    case ExtraFlagType::TYPE_NICK:
                        $serviceInfo['player_nick'] = htmlspecialchars($userService['auth_data']);
                        $styles['nick'] = $styles['password'] = "display: table-row";
                        break;

                    case ExtraFlagType::TYPE_IP:
                        $serviceInfo['player_ip'] = htmlspecialchars($userService['auth_data']);
                        $styles['ip'] = $styles['password'] = "display: table-row";
                        break;

                    case ExtraFlagType::TYPE_SID:
                        $serviceInfo['player_sid'] = htmlspecialchars($userService['auth_data']);
                        $styles['sid'] = "display: table-row";
                        break;
                }
            }
        }

        // Hasło
        if (strlen($userService['password']) && $userService['password'] != md5("")) {
            $serviceInfo['password'] = "********";
        }

        // Serwer
        $tmpServer = $this->heart->getServer($userService['server']);
        $serviceInfo['server'] = $tmpServer['name'];
        unset($tmpServer);

        // Wygasa
        $serviceInfo['expire'] =
            $userService['expire'] == -1
                ? $this->lang->translate('never')
                : date($this->settings['date_format'], $userService['expire']);

        // Usługa
        $serviceInfo['service'] = $this->service['name'];

        return $this->template->render(
            "services/extra_flags/user_own_service_edit",
            compact('serviceInfo', 'styles')
        );
    }

    public function userOwnServiceInfoGet($userService, $buttonEdit)
    {
        $serviceInfo['expire'] =
            $userService['expire'] == -1
                ? $this->lang->translate('never')
                : date($this->settings['date_format'], $userService['expire']);
        $tmpServer = $this->heart->getServer($userService['server']);
        $serviceInfo['server'] = $tmpServer['name'];
        $serviceInfo['service'] = $this->service['name'];
        $serviceInfo['type'] = $this->getTypeName2($userService['type']);
        $serviceInfo['auth_data'] = htmlspecialchars($userService['auth_data']);
        unset($tmpServer);

        return $this->template->render(
            "services/extra_flags/user_own_service",
            compact('userService', 'buttonEdit', 'serviceInfo') + [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

    public function userOwnServiceEdit(array $data, $userService)
    {
        $user = $this->auth->user();

        // Pobieramy auth_data
        $data['auth_data'] = $this->getAuthData($data);

        // Sprawdzamy, czy ustawiono hasło, gdy hasła nie ma w bazie i dana usługa wymaga hasła
        if (
            !strlen($data['password']) &&
            $data['type'] & (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP) &&
            !strlen($userService['password'])
        ) {
            $warnings['password'][] = $this->lang->translate('field_no_empty');
        }

        // Sprawdzamy poprawność wprowadzonych danych
        $verifyData = $this->verifyUserServiceData($data, $warnings, false);

        // Jeżeli są jakieś błędy, to je zwracamy
        if (!empty($verifyData)) {
            return $verifyData;
        }

        //
        // Aktualizujemy usługę

        $editReturn = $this->userServiceEdit($userService, [
            'type' => $data['type'],
            'auth_data' => $data['auth_data'],
            'password' => $data['password'],
        ]);

        if ($editReturn['status'] == 'ok') {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('user_edited_service'),
                    $user->getUsername(),
                    $user->getUid(),
                    $userService['id']
                )
            );
        }

        return $editReturn;
    }

    // ----------------------------------------------------------------------------------
    // ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

    private function userServiceEdit($userService, $data)
    {
        $set = [];
        // Dodanie hasła do zapytania
        if (strlen($data['password'])) {
            $set[] = [
                'column' => 'password',
                'value' => "'%s'",
                'data' => [$data['password']],
            ];
        }

        // Dodajemy uid do zapytania
        if (isset($data['uid'])) {
            $set[] = [
                'column' => 'uid',
                'value' => "'%d'",
                'data' => [$data['uid']],
            ];
        }

        // Dodajemy expire na zawsze
        if ($data['forever']) {
            $set[] = [
                'column' => 'expire',
                'value' => "-1",
            ];
        }

        // Sprawdzenie czy nie ma już takiej usługi
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT * FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE us.service = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND `id` != '%d'",
                [
                    $this->service['id'],
                    if_isset($data['server'], $userService['server']),
                    if_isset($data['type'], $userService['type']),
                    if_isset($data['auth_data'], $userService['auth_data']),
                    $userService['id'],
                ]
            )
        );

        // Jeżeli istnieje usługa o identycznych danych jak te, na które będziemy zmieniać obecną usługę
        if ($this->db->numRows($result)) {
            // Pobieramy tę drugą usługę
            $userService2 = $this->db->fetchArrayAssoc($result);

            if (!isset($data['uid']) && $userService['uid'] != $userService2['uid']) {
                return [
                    'status' => "service_exists",
                    'text' => $this->lang->translate('service_isnt_yours'),
                    'positive' => false,
                ];
            }

            // Usuwamy opcję którą aktualizujemy
            $this->db->query(
                $this->db->prepare(
                    "DELETE FROM `" . TABLE_PREFIX . "user_service` " . "WHERE `id` = '%d'",
                    [$userService['id']]
                )
            );

            // Dodajemy expire
            if (!$data['forever'] && isset($data['expire'])) {
                $set[] = [
                    'column' => 'expire',
                    'value' => "( `expire` - UNIX_TIMESTAMP() + '%d' )",
                    'data' => [if_isset($data['expire'], $userService['expire'])],
                ];
            }

            // Aktualizujemy usługę, która już istnieje w bazie i ma takie same dane jak nasze nowe
            $affected = $this->updateUserService($set, $userService2['id'], $userService2['id']);
        } else {
            $set[] = [
                'column' => 'service',
                'value' => "'%s'",
                'data' => [$this->service['id']],
            ];

            if (!$data['forever'] && isset($data['expire'])) {
                $set[] = [
                    'column' => 'expire',
                    'value' => "'%d'",
                    'data' => [$data['expire']],
                ];
            }

            if (isset($data['server'])) {
                $set[] = [
                    'column' => 'server',
                    'value' => "'%d'",
                    'data' => [$data['server']],
                ];
            }

            if (isset($data['type'])) {
                $set[] = [
                    'column' => 'type',
                    'value' => "'%d'",
                    'data' => [$data['type']],
                ];
            }

            if (isset($data['auth_data'])) {
                $set[] = [
                    'column' => 'auth_data',
                    'value' => "'%s'",
                    'data' => [$data['auth_data']],
                ];
            }

            $affected = $this->updateUserService($set, $userService['id'], $userService['id']);
        }

        // Ustaw jednakowe hasła
        // żeby potem nie było problemów z różnymi hasłami
        if (strlen($data['password'])) {
            $this->db->query(
                $this->db->prepare(
                    "UPDATE `" .
                        TABLE_PREFIX .
                        $this::USER_SERVICE_TABLE .
                        "` " .
                        "SET `password` = '%s' " .
                        "WHERE `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s'",
                    [
                        $data['password'],
                        if_isset($data['server'], $userService['server']),
                        if_isset($data['type'], $userService['type']),
                        if_isset($data['auth_data'], $userService['auth_data']),
                    ]
                )
            );
        }

        // Przelicz flagi tylko wtedy, gdy coś się zmieniło
        if (!$affected) {
            return [
                'status' => "not_edited",
                'text' => $this->lang->translate('not_edited_user_service'),
                'positive' => false,
            ];
        }

        // Odśwież flagi gracza ( przed zmiana danych )
        $this->recalculatePlayerFlags(
            $userService['server'],
            $userService['type'],
            $userService['auth_data']
        );

        // Odśwież flagi gracza ( już po edycji )
        $this->recalculatePlayerFlags(
            if_isset($data['server'], $userService['server']),
            if_isset($data['type'], $userService['type']),
            if_isset($data['auth_data'], $userService['auth_data'])
        );

        return [
            'status' => 'ok',
            'text' => $this->lang->translate('edited_user_service'),
            'positive' => true,
        ];
    }

    public function serviceTakeOverFormGet()
    {
        // Generujemy typy usługi
        $types = "";
        for ($i = 0; $i < 3; $i++) {
            $value = 1 << $i;
            if ($this->service['types'] & $value) {
                $types .= create_dom_element("option", $this->getTypeName($value), [
                    'value' => $value,
                ]);
            }
        }

        $servers = "";
        // Pobieranie listy serwerów
        foreach ($this->heart->getServers() as $id => $row) {
            $servers .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
            ]);
        }

        return $this->template->render(
            "services/extra_flags/service_take_over",
            compact('servers', 'types') + ['moduleId' => $this->getModuleId()]
        );
    }

    public function serviceTakeOver($data)
    {
        $user = $this->auth->user();

        // Serwer
        if (!strlen($data['server'])) {
            $warnings['server'][] = $this->lang->translate('field_no_empty');
        }

        // Typ
        if (!strlen($data['type'])) {
            $warnings['type'][] = $this->lang->translate('field_no_empty');
        }

        switch ($data['type']) {
            case "1":
                // Nick
                if (!strlen($data['nick'])) {
                    $warnings['nick'][] = $this->lang->translate('field_no_empty');
                }

                // Hasło
                if (!strlen($data['password'])) {
                    $warnings['password'][] = $this->lang->translate('field_no_empty');
                }

                $authData = $data['nick'];
                break;

            case "2":
                // IP
                if (!strlen($data['ip'])) {
                    $warnings['ip'][] = $this->lang->translate('field_no_empty');
                }

                // Hasło
                if (!strlen($data['password'])) {
                    $warnings['password'][] = $this->lang->translate('field_no_empty');
                }

                $authData = $data['ip'];
                break;

            case "4":
                // SID
                if (!strlen($data['sid'])) {
                    $warnings['sid'][] = $this->lang->translate('field_no_empty');
                }

                $authData = $data['sid'];
                break;
        }

        // Płatność
        if (!strlen($data['payment'])) {
            $warnings['payment'][] = $this->lang->translate('field_no_empty');
        }

        if (in_array($data['payment'], ["sms", "transfer"])) {
            if (!strlen($data['payment_id'])) {
                $warnings['payment_id'][] = $this->lang->translate('field_no_empty');
            }
        }

        // Jeżeli są jakieś błedy, to je zwróć
        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        if ($data['payment'] == "transfer") {
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM ({$this->settings['transactions_query']}) as t " .
                        "WHERE t.payment = 'transfer' AND t.payment_id = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
                    [$data['payment_id'], $this->service['id'], $data['server'], $authData]
                )
            );

            if (!$this->db->numRows($result)) {
                return [
                    'status' => "no_service",
                    'text' => $this->lang->translate('no_user_service'),
                    'positive' => false,
                ];
            }
        } else {
            if ($data['payment'] == "sms") {
                $result = $this->db->query(
                    $this->db->prepare(
                        "SELECT * FROM ({$this->settings['transactions_query']}) as t " .
                            "WHERE t.payment = 'sms' AND t.sms_code = '%s' AND `service` = '%s' AND `server` = '%d' AND `auth_data` = '%s'",
                        [$data['payment_id'], $this->service['id'], $data['server'], $authData]
                    )
                );

                if (!$this->db->numRows($result)) {
                    return [
                        'status' => "no_service",
                        'text' => $this->lang->translate('no_user_service'),
                        'positive' => false,
                    ];
                }
            }
        }

        // TODO: Usunac md5
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `id` FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS usef ON us.id = usef.us_id " .
                    "WHERE us.service = '%s' AND `server` = '%d' AND `type` = '%d' AND `auth_data` = '%s' AND ( `password` = '%s' OR `password` = '%s' )",
                [
                    $this->service['id'],
                    $data['server'],
                    $data['type'],
                    $authData,
                    $data['password'],
                    md5($data['password']),
                ]
            )
        );

        if (!$this->db->numRows($result)) {
            return [
                'status' => "no_service",
                'text' => $this->lang->translate('no_user_service'),
                'positive' => false,
            ];
        }

        $row = $this->db->fetchArrayAssoc($result);

        $this->db->query(
            $this->db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "user_service` " .
                    "SET `uid` = '%d' " .
                    "WHERE `id` = '%d'",
                [$user->getUid(), $row['id']]
            )
        );

        if (!$this->db->affectedRows()) {
            return [
                'status' => "service_not_taken_over",
                'text' => $this->lang->translate('service_not_taken_over'),
                'positive' => false,
            ];
        }

        return [
            'status' => "ok",
            'text' => $this->lang->translate('service_taken_over'),
            'positive' => true,
        ];
    }

    // ----------------------------------------------------------------------------------
    // ### Inne

    /**
     * Metoda zwraca listę serwerów na których można zakupić daną usługę
     *
     * @param integer $server
     *
     * @return string            Lista serwerów w postaci <option value="id_serwera">Nazwa</option>
     */
    private function serversForService($server)
    {
        if (!get_privileges("manage_user_services")) {
            throw new UnauthorizedException();
        }

        $servers = "";
        // Pobieranie serwerów na których można zakupić daną usługę
        foreach ($this->heart->getServers() as $id => $row) {
            if (!$this->heart->serverServiceLinked($id, $this->service['id'])) {
                continue;
            }

            $servers .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
                'selected' => $server == $row['id'] ? "selected" : "",
            ]);
        }

        return $servers;
    }

    /**
     * Funkcja zwraca listę dostępnych taryf danej usługi na danym serwerze
     *
     * @param integer $serverId
     *
     * @return string
     */
    private function tariffs_for_server($serverId)
    {
        $server = $this->heart->getServer($serverId);
        $smsService = if_strlen($server['sms_service'], $this->settings['sms_service']);

        // Pobieranie kwot za które można zakupić daną usługę na danym serwerze
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT sn.number AS `sms_number`, t.provision AS `provision`, t.id AS `tariff`, p.amount AS `amount` " .
                    "FROM `" .
                    TABLE_PREFIX .
                    "pricelist` AS p " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    "tariffs` AS t ON t.id = p.tariff " .
                    "LEFT JOIN `" .
                    TABLE_PREFIX .
                    "sms_numbers` AS sn ON sn.tariff = p.tariff AND sn.service = '%s' " .
                    "WHERE p.service = '%s' AND ( p.server = '%d' OR p.server = '-1' ) " .
                    "ORDER BY t.provision ASC",
                [$smsService, $this->service['id'], $serverId]
            )
        );

        $values = '';
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $provision = number_format($row['provision'] / 100, 2);
            $smsCost = strlen($row['sms_number'])
                ? number_format(
                    (get_sms_cost($row['sms_number']) / 100) * $this->settings['vat'],
                    2
                )
                : 0;
            $amount =
                $row['amount'] != -1
                    ? "{$row['amount']} {$this->service['tag']}"
                    : $this->lang->translate('forever');
            $values .= $this->template->render(
                "services/extra_flags/purchase_value",
                compact('provision', 'smsCost', 'row', 'amount'),
                true,
                false
            );
        }

        return $this->template->render(
            "services/extra_flags/tariffs_for_server",
            compact('values')
        );
    }

    public function actionExecute($action, $data)
    {
        switch ($action) {
            case "tariffs_for_server":
                return $this->tariffs_for_server(intval($data['server']));
            case "servers_for_service":
                return $this->serversForService(intval($data['server']));
            default:
                return '';
        }
    }

    public function serviceCodeValidate(Purchase $purchaseData, $code)
    {
        return true;
    }

    public function serviceCodeAdminAddFormGet()
    {
        // Pobieramy listę serwerów
        $servers = "";
        foreach ($this->heart->getServers() as $id => $row) {
            if (!$this->heart->serverServiceLinked($id, $this->service['id'])) {
                continue;
            }

            $servers .= create_dom_element("option", $row['name'], [
                'value' => $row['id'],
            ]);
        }

        return $this->template->render(
            "services/extra_flags/service_code_admin_add",
            compact('servers') + ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    public function serviceCodeAdminAddValidate($data)
    {
        $warnings = [];

        // Serwer
        if (!strlen($data['server'])) {
            $warnings['server'][] = $this->lang->translate('have_to_choose_server');
        }
        // Wyszukiwanie serwera o danym id
        elseif (($server = $this->heart->getServer($data['server'])) === null) {
            $warnings['server'][] = $this->lang->translate('no_server_id');
        }

        // Taryfa
        $tariff = explode(';', $data['amount']);
        $tariff = $tariff[2];
        if (!strlen($data['amount'])) {
            $warnings['amount'][] = $this->lang->translate('must_choose_quantity');
        } elseif ($this->heart->getTariff($tariff) === null) {
            $warnings['amount'][] = $this->lang->translate('no_such_tariff');
        }

        return $warnings;
    }

    public function serviceCodeAdminAddInsert($data)
    {
        $tariff = explode(';', $data['amount']);
        $tariff = $tariff[2];

        return [
            'tariff' => $tariff,
            'server' => $data['server'],
        ];
    }

    // Zwraca wartość w zależności od typu
    private function getAuthData($data)
    {
        if ($data['type'] == ExtraFlagType::TYPE_NICK) {
            return $data['nick'];
        }

        if ($data['type'] == ExtraFlagType::TYPE_IP) {
            return $data['ip'];
        }

        if ($data['type'] == ExtraFlagType::TYPE_SID) {
            return $data['sid'];
        }
    }

    private function maxMinus($a, $b)
    {
        if ($a == -1 || $b == -1) {
            return -1;
        }

        return max($a, $b);
    }
}
