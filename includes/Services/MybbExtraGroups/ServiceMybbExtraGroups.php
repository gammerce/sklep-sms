<?php
namespace App\Services\MybbExtraGroups;

use App\Exceptions\InvalidConfigException;
use App\Models\MybbUser;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\Services\Interfaces\IServicePurchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Services\Interfaces\IServiceUserOwnServices;
use App\Services\Interfaces\IServiceUserServiceAdminAdd;
use App\System\Auth;
use App\System\Database;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use PDOException;

class ServiceMybbExtraGroups extends ServiceMybbExtraGroupsSimple implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceUserServiceAdminAdd,
    IServiceUserOwnServices
{
    /** @var array */
    private $groups = [];

    private $dbHost;
    private $dbUser;
    private $dbPassword;
    private $dbName;

    /** @var Database */
    private $dbMybb = null;

    /** @var Translator */
    private $langShop;

    /** @var Auth */
    private $auth;

    /** @var Heart */
    private $heart;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->langShop = $translationManager->shop();
        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);

        $serviceData = $this->service ? $this->service->getData() : null;
        if (isset($serviceData['mybb_groups'])) {
            $this->groups = explode(",", $serviceData['mybb_groups']);
        }
        $this->dbHost = array_get($serviceData, 'db_host', '');
        $this->dbUser = array_get($serviceData, 'db_user', '');
        $this->dbPassword = array_get($serviceData, 'db_password', '');
        $this->dbName = array_get($serviceData, 'db_name', '');
    }

    public function purchaseFormGet(array $query)
    {
        $user = $this->auth->user();

        $paymentModule = $this->heart->getPaymentModuleByPlatformIdOrFail(
            $this->settings->getSmsPlatformId()
        );

        // Get tariffs
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
                [$paymentModule->getModuleId(), $this->service->getId()]
            )
        );

        $amounts = "";
        while ($row = $this->db->fetchArrayAssoc($result)) {
            $smsCost = strlen($row['sms_number'])
                ? number_format(
                    (get_sms_cost($row['sms_number']) / 100) * $this->settings['vat'],
                    2
                )
                : 0;
            $amount =
                $row['amount'] != -1
                    ? $row['amount'] . " " . $this->service->getTag()
                    : $this->lang->t('forever');
            $provision = number_format($row['provision'] / 100, 2);
            $amounts .= $this->template->render(
                "services/mybb_extra_groups/purchase_value",
                compact('provision', 'smsCost', 'row', 'amount'),
                true,
                false
            );
        }

        return $this->template->render(
            "services/mybb_extra_groups/purchase_form",
            compact('amounts', 'user') + ['serviceId' => $this->service->getId()]
        );
    }

    /**
     * Metoda wywoływana, gdy użytkownik wprowadzi dane w formularzu zakupu
     * i trzeba sprawdzić, czy są one prawidłowe
     *
     * @param array $data
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
        $warnings = [];

        // Tariff
        if (!$tariff) {
            $warnings['amount'][] = $this->lang->t('must_choose_amount');
        } else {
            // Wyszukiwanie usługi o konkretnej cenie
            $result = $this->db->query(
                $this->db->prepare(
                    "SELECT * FROM `" .
                        TABLE_PREFIX .
                        "pricelist` " .
                        "WHERE `service` = '%s' AND `tariff` = '%d'",
                    [$this->service->getId(), $tariff]
                )
            );

            if (!$this->db->numRows($result)) {
                // Brak takiej opcji w bazie ( ktoś coś edytował w htmlu strony )
                return [
                    'status' => "no_option",
                    'text' => $this->lang->t('service_not_affordable'),
                    'positive' => false,
                ];
            }

            $price = $this->db->fetchArrayAssoc($result);
        }

        // Username
        if (!strlen($data['username'])) {
            $warnings['username'][] = $this->lang->t('field_no_empty');
        } else {
            $this->connectMybb();

            $result = $this->dbMybb->query(
                $this->dbMybb->prepare("SELECT 1 FROM `mybb_users` " . "WHERE `username` = '%s'", [
                    $data['username'],
                ])
            );

            if (!$this->dbMybb->numRows($result)) {
                $warnings['username'][] = $this->lang->t('no_user');
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
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchaseData = new Purchase($this->auth->user());
        $purchaseData->setService($this->service->getId());
        $purchaseData->setOrder([
            'username' => $data['username'],
            'amount' => $price['amount'],
            'forever' => $price['amount'] == -1 ? true : false,
        ]);
        $purchaseData->setEmail($data['email']);
        $purchaseData->setTariff($this->heart->getTariff($tariff));

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
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
        $email = $purchaseData->getEmail() ?: $this->lang->t('none');
        $username = $purchaseData->getOrder('username');
        $serviceName = $this->service->getName();
        $amount =
            $purchaseData->getOrder('amount') != -1
                ? $purchaseData->getOrder('amount') . " " . $this->service->getTag()
                : $this->lang->t('forever');

        return $this->template->render(
            "services/mybb_extra_groups/order_details",
            compact('amount', 'username', 'email', 'serviceName'),
            true,
            false
        );
    }

    /**
     * Metoda wywoływana, gdy usługa została prawidłowo zakupiona
     *
     * @param Purchase $purchaseData
     *
     * @return integer        value returned by function addBoughtServiceInfo
     */
    public function purchase(Purchase $purchaseData)
    {
        // Nie znaleziono użytkownika o takich danych jak podane podczas zakupu
        if (($mybbUser = $this->createMybbUser($purchaseData->getOrder('username'))) === null) {
            log_to_db(
                $this->langShop->t(
                    'mybb_purchase_no_user',
                    json_encode($purchaseData->getPayment())
                )
            );
            die("Critical error occurred");
        }

        $this->userServiceAdd(
            $purchaseData->user->getUid(),
            $mybbUser->getUid(),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('forever')
        );
        foreach ($this->groups as $group) {
            $mybbUser->prolongShopGroup($group, $purchaseData->getOrder('amount') * 24 * 60 * 60);
        }
        $this->saveMybbUser($mybbUser);

        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service->getId(),
            0,
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('username') . " ({$mybbUser->getUid()})",
            $purchaseData->getEmail(),
            [
                'uid' => $mybbUser->getUid(),
                'groups' => implode(',', $this->groups),
            ]
        );
    }

    public function purchaseInfo($action, array $data)
    {
        $username = $data['auth_data'];
        $amount =
            $data['amount'] != -1
                ? $data['amount'] . " " . $this->service->getTag()
                : $this->lang->t('forever');
        $email = $data['email'];
        $cost = $data['cost']
            ? number_format($data['cost'] / 100.0, 2) . " " . $this->settings['currency']
            : $this->lang->t('none');

        if ($action == "email") {
            return $this->template->render(
                "services/mybb_extra_groups/purchase_info_email",
                compact('username', 'amount', 'cost') + [
                    'serviceName' => $this->service->getName(),
                ],
                true,
                false
            );
        }

        if ($action == "web") {
            return $this->template->render(
                "services/mybb_extra_groups/purchase_info_web",
                compact('cost', 'username', 'amount', 'email') + [
                    'serviceName' => $this->service->getName(),
                ],
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->t('mybb_group_bought', $this->service->getName(), $username),
                'class' => "outcome",
            ];
        }

        return '';
    }

    public function userServiceDelete($userService, $who)
    {
        try {
            $this->connectMybb();
        } catch (PDOException $e) {
            if ($who === 'admin') {
                throw new InvalidConfigException($e->getMessage());
            }

            return false;
        }

        return true;
    }

    public function userServiceDeletePost($userService)
    {
        $mybbUser = $this->createMybbUser(intval($userService['mybb_uid']));

        // Usuwamy wszystkie shopGroups oraz z mybbGroups te grupy, które maja was_before = false
        foreach ($mybbUser->getShopGroup() as $gid => $groupData) {
            if (!$groupData['was_before']) {
                $mybbUser->removeMybbAddGroup($gid);
            }
        }
        $mybbUser->removeShopGroup();

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
            foreach (explode(',', $row['extra_data']['mybb_groups']) as $groupId) {
                $mybbUser->prolongShopGroup($groupId, $row['expire']);
            }
        }

        // Użytkownik nie może mieć takiej displaygroup
        if (
            !in_array(
                $mybbUser->getMybbDisplayGroup(),
                array_unique(
                    array_merge(
                        array_keys($mybbUser->getShopGroup()),
                        $mybbUser->getMybbAddGroups(),
                        [$mybbUser->getMybbUserGroup()]
                    )
                )
            )
        ) {
            $mybbUser->setMybbDisplayGroup(0);
        }

        $this->saveMybbUser($mybbUser);
    }

    /**
     * Dodaje graczowi usłguę
     *
     * @param $uid
     * @param $mybbUid
     * @param $days
     * @param $forever
     */
    private function userServiceAdd($uid, $mybbUid, $days, $forever)
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
                [$this->service->getId(), $mybbUid]
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
                        'data' => [$mybbUid],
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
                    [$uid, $this->service->getId(), $forever, $days * 24 * 60 * 60]
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
                    [$userServiceId, $this->service->getId(), $mybbUid]
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
                    $warnings['amount'][] = $this->lang->t('days_quantity_positive');
                }
            }
        }

        // ID użytkownika
        if (strlen($body['uid'])) {
            if ($warning = check_for_warnings('uid', $body['uid'])) {
                $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
            } else {
                $editedUser = $this->heart->getUser($body['uid']);
                if (!$editedUser->exists()) {
                    $warnings['uid'][] = $this->lang->t('no_account_id');
                }
            }
        }

        // Username
        if (!strlen($body['mybb_username'])) {
            $warnings['mybb_username'][] = $this->lang->t('field_no_empty');
        } else {
            $this->connectMybb();

            $result = $this->dbMybb->query(
                $this->dbMybb->prepare("SELECT 1 FROM `mybb_users` " . "WHERE `username` = '%s'", [
                    $body['mybb_username'],
                ])
            );

            if (!$this->dbMybb->numRows($result)) {
                $warnings['mybb_username'][] = $this->lang->t('no_user');
            }
        }

        // E-mail
        if (strlen($body['email']) && ($warning = check_for_warnings("email", $body['email']))) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        if (!empty($warnings)) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        // Dodawanie informacji o płatności
        $paymentId = pay_by_admin($user);

        $purchaseData = new Purchase($this->heart->getUser($body['uid']));
        $purchaseData->setService($this->service->getId());
        $purchaseData->setPayment([
            'method' => "admin",
            'payment_id' => $paymentId,
        ]);
        $purchaseData->setOrder([
            'username' => $body['mybb_username'],
            'amount' => $body['amount'],
            'forever' => (bool) $body['forever'],
        ]);
        $purchaseData->setEmail($body['email']);
        $boughtServiceId = $this->purchase($purchaseData);

        log_to_db(
            $this->langShop->t(
                'admin_added_user_service',
                $user->getUsername(),
                $user->getUid(),
                $boughtServiceId
            )
        );

        return [
            'status' => "ok",
            'text' => $this->lang->t('service_added_correctly'),
            'positive' => true,
        ];
    }

    public function userOwnServiceInfoGet($userService, $buttonEdit)
    {
        $this->connectMybb();

        $username = $this->dbMybb->getColumn(
            $this->dbMybb->prepare("SELECT `username` FROM `mybb_users` " . "WHERE `uid` = '%d'", [
                $userService['mybb_uid'],
            ]),
            'username'
        );

        $expire =
            $userService['expire'] == -1
                ? $this->lang->t('never')
                : date($this->settings['date_format'], $userService['expire']);
        $serviceName = $this->service->getName();
        $mybbUid = "$username ({$userService['mybb_uid']})";

        return $this->template->render(
            "services/mybb_extra_groups/user_own_service",
            compact('userService', 'serviceName', 'mybbUid', 'expire') + [
                'moduleId' => $this->getModuleId(),
            ]
        );
    }

    /**
     * @param string|int $userId Int - by uid, String - by username
     * @return null|MybbUser
     */
    private function createMybbUser($userId)
    {
        $this->connectMybb();

        if (is_integer($userId)) {
            $where = "`uid` = {$userId}";
        } else {
            $where = $this->dbMybb->prepare("`username` = '%s'", [$userId]);
        }

        $result = $this->dbMybb->query(
            "SELECT `uid`, `additionalgroups`, `displaygroup`, `usergroup` " .
                "FROM `mybb_users` " .
                "WHERE {$where}"
        );

        if (!$this->dbMybb->numRows($result)) {
            return null;
        }

        $rowMybb = $this->dbMybb->fetchArrayAssoc($result);

        $mybbUser = new MybbUser($rowMybb['uid'], $rowMybb['usergroup']);
        $mybbUser->setMybbAddGroups(explode(",", $rowMybb['additionalgroups']));
        $mybbUser->setMybbDisplayGroup($rowMybb['displaygroup']);

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `gid`, UNIX_TIMESTAMP(`expire`) - UNIX_TIMESTAMP() AS `expire`, `was_before` FROM `" .
                    TABLE_PREFIX .
                    "mybb_user_group` " .
                    "WHERE `uid` = '%d'",
                [$rowMybb['uid']]
            )
        );

        while ($row = $this->db->fetchArrayAssoc($result)) {
            $mybbUser->setShopGroup($row['gid'], [
                'expire' => $row['expire'],
                'was_before' => $row['was_before'],
            ]);
        }

        return $mybbUser;
    }

    /**
     * Zapisuje dane o użytkowniku
     *
     * @param MybbUser $mybbUser
     */
    private function saveMybbUser($mybbUser)
    {
        $this->connectMybb();

        $this->db->query(
            $this->db->prepare(
                "DELETE FROM `" . TABLE_PREFIX . "mybb_user_group` " . "WHERE `uid` = '%d'",
                [$mybbUser->getUid()]
            )
        );

        $values = [];
        foreach ($mybbUser->getShopGroup() as $gid => $groupData) {
            $values[] = $this->db->prepare(
                "('%d', '%d', FROM_UNIXTIME(UNIX_TIMESTAMP() + %d), '%d')",
                [$mybbUser->getUid(), $gid, $groupData['expire'], $groupData['was_before']]
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
            array_merge(array_keys($mybbUser->getShopGroup()), $mybbUser->getMybbAddGroups())
        );

        $this->dbMybb->query(
            $this->dbMybb->prepare(
                "UPDATE `mybb_users` " .
                    "SET `additionalgroups` = '%s', `displaygroup` = '%d' " .
                    "WHERE `uid` = '%d'",
                [implode(',', $addgroups), $mybbUser->getMybbDisplayGroup(), $mybbUser->getUid()]
            )
        );
    }

    /**
     * @throws PDOException
     */
    private function connectMybb()
    {
        if ($this->dbMybb !== null) {
            return;
        }

        $this->dbMybb = new Database(
            $this->dbHost,
            3306,
            $this->dbUser,
            $this->dbPassword,
            $this->dbName
        );
        $this->dbMybb->connect();
    }
}
