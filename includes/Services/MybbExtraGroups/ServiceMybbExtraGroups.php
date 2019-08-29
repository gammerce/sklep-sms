<?php
namespace App\Services\MybbExtraGroups;

use App\Auth;
use App\Database;
use App\Exceptions\SqlQueryException;
use App\Heart;
use App\Models\MybbUser;
use App\Models\Purchase;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\TranslationManager;
use App\Translator;

class ServiceMybbExtraGroups extends ServiceMybbExtraGroupsSimple implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceUserServiceAdminAdd,
    IServiceUserOwnServices
{
    /**
     * @var array
     */
    private $groups;

    private $db_host;
    private $db_user;
    private $db_password;
    private $db_name;

    /** @var Database */
    protected $db_mybb = null;

    /** @var Translator */
    protected $langShop;

    /** @var Auth */
    protected $auth;

    /** @var Heart */
    protected $heart;

    public function __construct($service)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->langShop = $translationManager->shop();
        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);

        $this->groups = explode(",", $this->service['data']['mybb_groups']);
        $this->db_host = if_isset($this->service['data']['db_host'], '');
        $this->db_user = if_isset($this->service['data']['db_user'], '');
        $this->db_password = if_isset($this->service['data']['db_password'], '');
        $this->db_name = if_isset($this->service['data']['db_name'], '');
    }

    /**
     * Metoda powinna zwracać formularz zakupu w postaci stringa
     *
     * @return string   - Formularz zakupu
     */
    public function purchaseFormGet()
    {
        $user = $this->auth->user();

        // Pozyskujemy taryfy
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
                    "WHERE p.service = '%s' " .
                    "ORDER BY t.provision ASC",
                [$this->settings['sms_service'], $this->service['id']]
            )
        );

        $amounts = "";
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $sms_cost = strlen($row['sms_number'])
                ? number_format(
                    (get_sms_cost($row['sms_number']) / 100) * $this->settings['vat'],
                    2
                )
                : 0;
            $amount =
                $row['amount'] != -1
                    ? $row['amount'] . " " . $this->service['tag']
                    : $this->lang->translate('forever');
            $provision = number_format($row['provision'] / 100, 2);
            $amounts .= $this->template->render(
                "services/mybb_extra_groups/purchase_value",
                compact('provision', 'sms_cost', 'row', 'amount'),
                true,
                false
            );
        }

        return $this->template->render(
            "services/mybb_extra_groups/purchase_form",
            compact('amounts', 'user') + ['serviceId' => $this->service['id']]
        );
    }

    /**
     * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
     * i trzeba sprawdzić, czy są one prawidłowe
     *
     * @param array $data Dane $_POST
     *
     * @return array        'status'    => id wiadomości,
     *                        'text'        => treść wiadomości
     *                        'positive'    => czy udało się przeprowadzić zakup czy nie
     */
    public function purchaseFormValidate($data)
    {
        // Amount
        $amount = explode(';', $data['amount']); // Wyłuskujemy taryfę
        $tariff = $amount[2];

        // Tariff
        if (!$tariff) {
            $warnings['amount'][] = $this->lang->translate('must_choose_amount');
        } else {
            // Wyszukiwanie usługi o konkretnej cenie
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" .
                        TABLE_PREFIX .
                        "pricelist` " .
                        "WHERE `service` = '%s' AND `tariff` = '%d'",
                    [$this->service['id'], $tariff]
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

        // Username
        if (!strlen($data['username'])) {
            $warnings['username'][] = $this->lang->translate('field_no_empty');
        } else {
            $this->connectMybb();

            $result = $this->db_mybb->query(
                $this->db_mybb->prepare("SELECT 1 FROM `mybb_users` " . "WHERE `username` = '%s'", [
                    $data['username'],
                ])
            );

            if (!$this->db_mybb->numRows($result)) {
                $warnings['username'][] = $this->lang->translate('no_user');
            }
        }

        // E-mail
        if ($warning = check_for_warnings("email", $data['email'])) {
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

        $purchaseData = new Purchase();
        $purchaseData->setService($this->service['id']);
        $purchaseData->setOrder([
            'username' => $data['username'],
            'amount' => $price['amount'],
            'forever' => $price['amount'] == -1 ? true : false,
        ]);
        $purchaseData->setEmail($data['email']);
        $purchaseData->setTariff($this->heart->getTariff($tariff));

        return [
            'status' => "ok",
            'text' => $this->lang->translate('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    /**
     * Metoda zwraca szczegóły zamówienia, wyświetlane podczas zakupu usługi, przed płatnością.
     *
     * @param Purchase $purchaseData
     *
     * @return string        Szczegóły zamówienia
     */
    public function orderDetails(Purchase $purchaseData)
    {
        $email = $purchaseData->getEmail()
            ? htmlspecialchars($purchaseData->getEmail())
            : $this->lang->translate('none');
        $username = htmlspecialchars($purchaseData->getOrder('username'));
        $amount =
            $purchaseData->getOrder('amount') != -1
                ? $purchaseData->getOrder('amount') . " " . $this->service['tag']
                : $this->lang->translate('forever');

        return $this->template->render(
            "services/mybb_extra_groups/order_details",
            compact('amount', 'username', 'email') + ['serviceName' => $this->service['name']],
            true,
            false
        );
    }

    /**
     * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
     *
     * @param Purchase $purchaseData
     *
     * @return integer        value returned by function add_bought_service_info
     */
    public function purchase(Purchase $purchaseData)
    {
        // Nie znaleziono użytkownika o takich danych jak podane podczas zakupu
        if (($mybb_user = $this->createMybbUser($purchaseData->getOrder('username'))) === null) {
            log_info(
                $this->langShop->sprintf(
                    $this->langShop->translate('mybb_purchase_no_user'),
                    json_encode($purchaseData->getPayment())
                )
            );
            die("Critical error occured");
        }

        $this->user_service_add(
            $purchaseData->user->getUid(),
            $mybb_user->getUid(),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('forever')
        );
        foreach ($this->groups as $group) {
            $mybb_user->prolongShopGroup($group, $purchaseData->getOrder('amount') * 24 * 60 * 60);
        }
        $this->saveMybbUser($mybb_user);

        return add_bought_service_info(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service['id'],
            0,
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('username') . " ({$mybb_user->getUid()})",
            $purchaseData->getEmail(),
            [
                'uid' => $mybb_user->getUid(),
                'groups' => implode(',', $this->groups),
            ]
        );
    }

    /**
     * Metoda formatuje i zwraca informacje o zakupionej usłudze, zaraz po jej zakupie.
     *
     * @param string $action Do czego zostaną te dane użyte ( email, web, payment_log )
     *                            email - wiadomość wysłana na maila o zakupie usługi
     *                            web - informacje wyświetlone na stronie WWW zaraz po zakupie
     *                            payment_log - wpis w historii płatności
     * @param array  $data Dane o zakupie usługi, zwrócone przez zapytanie zdefiniowane w global.php
     *
     * @return string|array        Informacje o zakupionej usłudze
     */
    public function purchaseInfo($action, $data)
    {
        $username = htmlspecialchars($data['auth_data']);
        $amount =
            $data['amount'] != -1
                ? $data['amount'] . " " . $this->service['tag']
                : $this->lang->translate('forever');
        $email = htmlspecialchars($data['email']);
        $cost = $data['cost']
            ? number_format($data['cost'] / 100.0, 2) . " " . $this->settings['currency']
            : $this->lang->translate('none');

        if ($action == "email") {
            return $this->template->render(
                "services/mybb_extra_groups/purchase_info_email",
                compact('username', 'amount', 'cost') + ['serviceName' => $this->service['name']],
                true,
                false
            );
        }

        if ($action == "web") {
            return $this->template->render(
                "services/mybb_extra_groups/purchase_info_web",
                compact('cost', 'username', 'amount', 'email') + [
                    'serviceName' => $this->service['name'],
                ],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => ($output = $this->lang->sprintf(
                    $this->lang->translate('mybb_group_bought'),
                    $this->service['name'],
                    $username
                )),
                'class' => "outcome",
            ];
        }

        return '';
    }

    public function userServiceDelete($userService, $who)
    {
        try {
            $this->connectMybb();
        } catch (SqlQueryException $e) {
            if ($who == 'admin') {
                output_page($e->getError());
            }

            return false;
        }

        return true;
    }

    public function userServiceDeletePost($userService)
    {
        $mybb_user = $this->createMybbUser(intval($userService['mybb_uid']));

        // Usuwamy wszystkie shopGroups oraz z mybbGroups te grupy, które maja was_before = false
        foreach ($mybb_user->getShopGroup() as $gid => $group_data) {
            if (!$group_data['was_before']) {
                $mybb_user->removeMybbAddGroup($gid);
            }
        }
        $mybb_user->removeShopGroup();

        // Dodajemy uzytkownikowi grupy na podstawie USER_SERVICE_TABLE
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT us.expire - UNIX_TIMESTAMP() AS `expire`, s.data AS `extra_data` FROM `" .
                    TABLE_PREFIX .
                    "user_service` AS us " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` AS m ON us.id = m.us_id " .
                    "INNER JOIN `" .
                    TABLE_PREFIX .
                    "services` AS s ON us.service = s.id " .
                    "WHERE m.mybb_uid = '%d'",
                [$userService['mybb_uid']]
            )
        );

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $row['extra_data'] = json_decode($row['extra_data'], true);
            foreach (explode(',', $row['extra_data']['mybb_groups']) as $group_id) {
                $mybb_user->prolongShopGroup($group_id, $row['expire']);
            }
        }

        // Użytkownik nie może mieć takiej displaygroup
        if (
            !in_array(
                $mybb_user->getMybbDisplayGroup(),
                array_unique(
                    array_merge(
                        array_keys($mybb_user->getShopGroup()),
                        $mybb_user->getMybbAddGroups(),
                        [$mybb_user->getMybbUserGroup()]
                    )
                )
            )
        ) {
            $mybb_user->setMybbDisplayGroup(0);
        }

        $this->saveMybbUser($mybb_user);
    }

    /**
     * Dodaje graczowi usłguę
     *
     * @param $uid
     * @param $mybb_uid
     * @param $days
     * @param $forever
     */
    private function user_service_add($uid, $mybb_uid, $days, $forever)
    {
        // Dodajemy usługę gracza do listy usług
        // Jeżeli już istnieje dokładnie taka sama, to ją przedłużamy
        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `us_id` FROM `" .
                    TABLE_PREFIX .
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "WHERE `service` = '%s' AND `mybb_uid` = '%d'",
                [$this->service['id'], $mybb_uid]
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
                        'column' => 'mybb_uid',
                        'value' => "'%d'",
                        'data' => [$mybb_uid],
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
                        "` (`us_id`, `service`, `mybb_uid`) " .
                        "VALUES ('%d', '%s', '%d')",
                    [$userServiceId, $this->service['id'], $mybb_uid]
                )
            );
        }
    }

    /**
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania usługi użytkownikowi
     *
     * @return string
     */
    public function userServiceAdminAddFormGet()
    {
        return $this->template->render(
            "services/mybb_extra_groups/user_service_admin_add",
            ['moduleId' => $this->getModuleId()],
            true,
            false
        );
    }

    public function userServiceAdminAdd($body)
    {
        $user = $this->auth->user();

        $warnings = [];

        // Amount
        if (!$body['forever']) {
            if ($warning = check_for_warnings("number", $body['amount'])) {
                $warnings['amount'] = array_merge((array) $warnings['amount'], $warning);
            } else {
                if ($body['amount'] < 0) {
                    $warnings['amount'][] = $this->lang->translate('days_quantity_positive');
                }
            }
        }

        // ID użytkownika
        if (strlen($body['uid'])) {
            if ($warning = check_for_warnings('uid', $body['uid'])) {
                $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
            } else {
                $user2 = $this->heart->getUser($body['uid']);
                if (!$user2->exists()) {
                    $warnings['uid'][] = $this->lang->translate('no_account_id');
                }
            }
        }

        // Username
        if (!strlen($body['mybb_username'])) {
            $warnings['mybb_username'][] = $this->lang->translate('field_no_empty');
        } else {
            $this->connectMybb();

            $result = $this->db_mybb->query(
                $this->db_mybb->prepare("SELECT 1 FROM `mybb_users` " . "WHERE `username` = '%s'", [
                    $body['mybb_username'],
                ])
            );

            if (!$this->db_mybb->numRows($result)) {
                $warnings['mybb_username'][] = $this->lang->translate('no_user');
            }
        }

        // E-mail
        if (strlen($body['email']) && ($warning = check_for_warnings("email", $body['email']))) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->translate('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        // Dodawanie informacji o płatności
        $payment_id = pay_by_admin($user);

        $purchaseData = new Purchase();
        $purchaseData->setService($this->service['id']);
        $purchaseData->user = $this->heart->getUser($body['uid']);
        $purchaseData->setPayment([
            'method' => "admin",
            'payment_id' => $payment_id,
        ]);
        $purchaseData->setOrder([
            'username' => $body['mybb_username'],
            'amount' => $body['amount'],
            'forever' => (bool) $body['forever'],
        ]);
        $purchaseData->setEmail($body['email']);
        $bought_service_id = $this->purchase($purchaseData);

        log_info(
            $this->langShop->sprintf(
                $this->langShop->translate('admin_added_user_service'),
                $user->getUsername(),
                $user->getUid(),
                $bought_service_id
            )
        );

        return [
            'status' => "ok",
            'text' => $this->lang->translate('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function userOwnServiceInfoGet($userService, $buttonEdit)
    {
        $this->connectMybb();

        $username = $this->db_mybb->getColumn(
            $this->db_mybb->prepare("SELECT `username` FROM `mybb_users` " . "WHERE `uid` = '%d'", [
                $userService['mybb_uid'],
            ]),
            'username'
        );

        $expire =
            $userService['expire'] == -1
                ? $this->lang->translate('never')
                : date($this->settings['date_format'], $userService['expire']);
        $service = $this->service['name'];
        $mybb_uid = htmlspecialchars($username . " ({$userService['mybb_uid']})");

        return $this->template->render(
            "services/mybb_extra_groups/user_own_service",
            compact('userService', 'service', 'mybb_uid', 'expire') + [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

    /**
     * @param string|int $user_id Int - by uid, String - by username
     * @return null|MybbUser
     */
    private function createMybbUser($user_id)
    {
        $this->connectMybb();

        if (is_integer($user_id)) {
            $where = "`uid` = {$user_id}";
        } else {
            $where = $this->db_mybb->prepare("`username` = '%s'", [$user_id]);
        }

        $result = $this->db_mybb->query(
            "SELECT `uid`, `additionalgroups`, `displaygroup`, `usergroup` " .
                "FROM `mybb_users` " .
                "WHERE {$where}"
        );

        if (!$this->db_mybb->numRows($result)) {
            return null;
        }

        $row_mybb = $this->db_mybb->fetchArrayAssoc($result);

        $mybb_user = new MybbUser($row_mybb['uid'], $row_mybb['usergroup']);
        $mybb_user->setMybbAddGroups(explode(",", $row_mybb['additionalgroups']));
        $mybb_user->setMybbDisplayGroup($row_mybb['displaygroup']);

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `gid`, UNIX_TIMESTAMP(`expire`) - UNIX_TIMESTAMP() AS `expire`, `was_before` FROM `" .
                    TABLE_PREFIX .
                    "mybb_user_group` " .
                    "WHERE `uid` = '%d'",
                [$row_mybb['uid']]
            )
        );

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $mybb_user->setShopGroup($row['gid'], [
                'expire' => $row['expire'],
                'was_before' => $row['was_before'],
            ]);
        }

        return $mybb_user;
    }

    /**
     * Zapisuje dane o użytkowniku
     *
     * @param MybbUser $mybb_user
     */
    private function saveMybbUser($mybb_user)
    {
        $this->connectMybb();

        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "mybb_user_group` " . "WHERE `uid` = '%d'",
                [$mybb_user->getUid()]
            )
        );

        $values = [];
        foreach ($mybb_user->getShopGroup() as $gid => $group_data) {
            $values[] = $this->db->prepare(
                "('%d', '%d', FROM_UNIXTIME(UNIX_TIMESTAMP() + %d), '%d')",
                [$mybb_user->getUid(), $gid, $group_data['expire'], $group_data['was_before']]
            );
        }

        if (!empty($values)) {
            $this->db->query(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "mybb_user_group` (`uid`, `gid`, `expire`, `was_before`) " .
                    "VALUES " .
                    implode(", ", $values)
            );
        }

        $addgroups = array_unique(
            array_merge(array_keys($mybb_user->getShopGroup()), $mybb_user->getMybbAddGroups())
        );

        $this->db_mybb->query(
            $this->db_mybb->prepare(
                "UPDATE `mybb_users` " .
                    "SET `additionalgroups` = '%s', `displaygroup` = '%d' " .
                    "WHERE `uid` = '%d'",
                [implode(',', $addgroups), $mybb_user->getMybbDisplayGroup(), $mybb_user->getUid()]
            )
        );
    }

    /**
     * @throws SqlQueryException
     */
    private function connectMybb()
    {
        if ($this->db_mybb !== null) {
            return;
        }

        $this->db_mybb = new Database(
            $this->db_host,
            3306,
            $this->db_user,
            $this->db_password,
            $this->db_name
        );
        $this->db_mybb->query("SET NAMES utf8");
    }
}
