<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Exceptions\InvalidConfigException;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\IntegerCommaSeparatedListRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\MybbUserExistsRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\PriceAvailableRule;
use App\Http\Validation\Rules\PriceExistsRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Rules\YesNoRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Models\MybbExtraGroupsUserService;
use App\Models\MybbUser;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\UserService;
use App\Payment\AdminPaymentService;
use App\Payment\BoughtServiceService;
use App\Payment\PurchasePriceService;
use App\Payment\PurchaseValidationService;
use App\Repositories\PriceRepository;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\Support\Database;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\HeadCell;
use App\View\Html\Structure;
use App\View\Html\Wrapper;
use App\View\Renders\PurchasePriceRenderer;
use PDOException;
use UnexpectedValueException;

class MybbExtraGroupsServiceModule extends ServiceModule implements
    IServiceAdminManage,
    IServiceCreate,
    IServiceUserServiceAdminDisplay,
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceUserServiceAdminAdd,
    IServiceUserOwnServices
{
    const MODULE_ID = "mybb_extra_groups";
    const USER_SERVICE_TABLE = "ss_user_service_mybb_extra_groups";

    /** @var array */
    private $groups = [];

    private $dbHost;
    private $dbUser;
    private $dbPassword;
    private $dbName;

    /** @var Database */
    private $dbMybb = null;

    /** @var Auth */
    private $auth;

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var AdminPaymentService */
    private $adminPaymentService;

    /** @var PurchasePriceService */
    private $purchasePriceService;

    /** @var PurchasePriceRenderer */
    private $purchasePriceRenderer;

    /** @var PriceRepository */
    private $priceRepository;

    /** @var PurchaseValidationService */
    private $purchaseValidationService;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->auth = $this->app->make(Auth::class);
        $this->heart = $this->app->make(Heart::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
        $this->adminPaymentService = $this->app->make(AdminPaymentService::class);
        $this->purchasePriceService = $this->app->make(PurchasePriceService::class);
        $this->purchasePriceRenderer = $this->app->make(PurchasePriceRenderer::class);
        $this->purchaseValidationService = $this->app->make(PurchaseValidationService::class);
        $this->priceRepository = $this->app->make(PriceRepository::class);
        $this->settings = $this->app->make(Settings::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();

        $serviceData = $this->service ? $this->service->getData() : null;
        if (isset($serviceData['mybb_groups'])) {
            $this->groups = explode(",", $serviceData['mybb_groups']);
        }
        $this->dbHost = array_get($serviceData, 'db_host', '');
        $this->dbUser = array_get($serviceData, 'db_user', '');
        $this->dbPassword = array_get($serviceData, 'db_password', '');
        $this->dbName = array_get($serviceData, 'db_name', '');
    }

    /**
     * @param array $data
     * @return MybbExtraGroupsUserService
     */
    public function mapToUserService(array $data)
    {
        return new MybbExtraGroupsUserService(
            as_int($data['id']),
            $data['service'],
            as_int($data['uid']),
            as_int($data['expire']),
            as_int($data['mybb_uid'])
        );
    }

    public function serviceAdminExtraFieldsGet()
    {
        // WEB
        if ($this->showOnWeb()) {
            $webSelYes = "selected";
            $webSelNo = "";
        } else {
            $webSelYes = "";
            $webSelNo = "selected";
        }

        // We're in the edit mode
        if ($this->service !== null) {
            // DB
            $dbPassword = strlen(array_get($this->service->getData(), 'db_password'))
                ? "********"
                : "";
            $dbHost = array_get($this->service->getData(), 'db_host');
            $dbUser = array_get($this->service->getData(), 'db_user');
            $dbName = array_get($this->service->getData(), 'db_name');

            // MyBB groups
            $mybbGroups = array_get($this->service->getData(), 'mybb_groups');
        }

        return $this->template->renderNoComments(
            "services/mybb_extra_groups/extra_fields",
            compact(
                'webSelNo',
                'webSelYes',
                'mybbGroups',
                'dbHost',
                'dbUser',
                'dbPassword',
                'dbName'
            ) + ['moduleId' => $this->getModuleId()]
        );
    }

    public function serviceAdminManagePre(Validator $validator)
    {
        $validator->extendRules([
            'db_host' => [new RequiredRule()],
            'db_user' => [new RequiredRule()],
            'db_password' => [],
            'db_name' => [new RequiredRule()],
            'mybb_groups' => [new RequiredRule(), new IntegerCommaSeparatedListRule()],
            'web' => [new RequiredRule(), new YesNoRule()],
        ]);
    }

    public function serviceAdminManagePost(array $body)
    {
        $mybbGroups = explode(",", $body['mybb_groups']);
        foreach ($mybbGroups as $key => $group) {
            $mybbGroups[$key] = trim($group);
            if (!strlen($mybbGroups[$key])) {
                unset($mybbGroups[$key]);
            }
        }

        $extraData = [
            'mybb_groups' => implode(",", $mybbGroups),
            'web' => $body['web'],
            'db_host' => $body['db_host'],
            'db_user' => $body['db_user'],
            'db_password' => array_get(
                $body,
                'db_password',
                array_get($this->service->getData(), 'db_password')
            ),
            'db_name' => $body['db_name'],
        ];

        return [
            'data' => $extraData,
        ];
    }

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->t('mybb_groups');
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        $pageNumber = $currentPage->getPageNumber();

        $wrapper = new Wrapper();
        $wrapper->setSearch();

        $table = new Structure();
        $table->addHeadCell(new HeadCell($this->lang->t('id'), "id"));
        $table->addHeadCell(new HeadCell($this->lang->t('user')));
        $table->addHeadCell(new HeadCell($this->lang->t('service')));
        $table->addHeadCell(new HeadCell($this->lang->t('mybb_user')));
        $table->addHeadCell(new HeadCell($this->lang->t('expires')));

        // Wyszukujemy dane ktore spelniaja kryteria
        $where = '';
        if (isset($query['search'])) {
            searchWhere(
                ["us.id", "us.uid", "u.username", "s.name", "usmeg.mybb_uid"],
                $query['search'],
                $where
            );
        }
        // Jezeli jest jakis where, to dodajemy WHERE
        if (strlen($where)) {
            $where = "WHERE " . $where . ' ';
        }

        $result = $this->db->query(
            "SELECT SQL_CALC_FOUND_ROWS us.id, us.uid, u.username, " .
                "s.id AS `service_id`, s.name AS `service`, us.expire, usmeg.mybb_uid " .
                "FROM `ss_user_service` AS us " .
                "INNER JOIN `" .
                $this::USER_SERVICE_TABLE .
                "` AS usmeg ON usmeg.us_id = us.id " .
                "LEFT JOIN `ss_services` AS s ON s.id = usmeg.service " .
                "LEFT JOIN `ss_users` AS u ON u.uid = us.uid " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT " .
                get_row_limit($pageNumber)
        );

        $table->setDbRowsCount($this->db->query('SELECT FOUND_ROWS()')->fetchColumn());

        foreach ($result as $row) {
            $bodyRow = new BodyRow();

            $bodyRow->setDbId($row['id']);
            $bodyRow->addCell(
                new Cell(
                    $row['uid'] ? $row['username'] . " ({$row['uid']})" : $this->lang->t('none')
                )
            );
            $bodyRow->addCell(new Cell($row['service']));
            $bodyRow->addCell(new Cell($row['mybb_uid']));
            $bodyRow->addCell(
                new Cell(
                    $row['expire'] == '-1'
                        ? $this->lang->t('never')
                        : date($this->settings->getDateFormat(), $row['expire'])
                )
            );
            if (get_privileges("manage_user_services")) {
                $bodyRow->setDeleteAction(true);
                $bodyRow->setEditAction(false);
            }

            $table->addBodyRow($bodyRow);
        }

        $wrapper->setTable($table);

        return $wrapper;
    }

    public function purchaseFormGet(array $query)
    {
        $user = $this->auth->user();

        $quantities = array_map(function (array $price) {
            return $this->purchasePriceRenderer->render($price, $this->service);
        }, $this->purchasePriceService->getServicePrices($this->service));

        return $this->template->render(
            "services/mybb_extra_groups/purchase_form",
            compact('quantities', 'user') + ['serviceId' => $this->service->getId()]
        );
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        $this->connectMybb();

        $validator = new Validator(
            [
                'email' => array_get($body, 'email'),
                'price_id' => array_get($body, "price_id"),
                'username' => array_get($body, 'username'),
            ],
            [
                'email' => [new RequiredRule(), new EmailRule()],
                'price_id' => [
                    new RequiredRule(),
                    new PriceExistsRule(),
                    new PriceAvailableRule($this->service),
                ],
                'username' => [new RequiredRule(), new MybbUserExistsRule($this->dbMybb)],
            ]
        );

        $validated = $validator->validateOrFail();

        $purchase->setOrder([
            'username' => $validated['username'],
        ]);
        $purchase->setEmail($validated['email']);
        $purchase->setPrice($this->priceRepository->get($validated['price_id']));
    }

    public function orderDetails(Purchase $purchase)
    {
        $email = $purchase->getEmail() ?: $this->lang->t('none');
        $username = $purchase->getOrder('username');
        $serviceName = $this->service->getName();
        $quantity = $purchase->getOrder(Purchase::ORDER_FOREVER)
            ? $this->lang->t('forever')
            : $purchase->getOrder(Purchase::ORDER_QUANTITY) . " " . $this->service->getTag();

        return $this->template->renderNoComments(
            "services/mybb_extra_groups/order_details",
            compact('quantity', 'username', 'email', 'serviceName')
        );
    }

    public function purchase(Purchase $purchase)
    {
        $mybbUser = $this->findMybbUser($purchase->getOrder('username'));

        // Nie znaleziono użytkownika o takich danych jak podane podczas zakupu
        if (!$mybbUser) {
            $this->logger->log('mybb_purchase_no_user', json_encode($purchase->getPayment()));
            die("Critical error occurred");
        }

        $this->userServiceAdd(
            $purchase->user->getUid(),
            $mybbUser->getUid(),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder(Purchase::ORDER_FOREVER)
        );
        foreach ($this->groups as $group) {
            $mybbUser->prolongShopGroup(
                $group,
                $purchase->getOrder(Purchase::ORDER_QUANTITY) * 24 * 60 * 60
            );
        }
        $this->saveMybbUser($mybbUser);

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder('username') . " ({$mybbUser->getUid()})",
            $purchase->getEmail(),
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
            ? number_format($data['cost'] / 100.0, 2) . " " . $this->settings->getCurrency()
            : $this->lang->t('none');

        if ($action == "email") {
            return $this->template->renderNoComments(
                "services/mybb_extra_groups/purchase_info_email",
                compact('username', 'amount', 'cost') + [
                    'serviceName' => $this->service->getName(),
                ]
            );
        }

        if ($action == "web") {
            return $this->template->renderNoComments(
                "services/mybb_extra_groups/purchase_info_web",
                compact('cost', 'username', 'amount', 'email') + [
                    'serviceName' => $this->service->getName(),
                ]
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

    public function userServiceDelete(UserService $userService, $who)
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

    public function userServiceDeletePost(UserService $userService)
    {
        if (!($userService instanceof MybbExtraGroupsUserService)) {
            throw new UnexpectedValueException();
        }

        $mybbUser = $this->findMybbUser($userService->getMybbUid());

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
                "SELECT us.expire - UNIX_TIMESTAMP() AS `expire`, s.data AS `extra_data` FROM `ss_user_service` AS us " .
                    "INNER JOIN `" .
                    $this::USER_SERVICE_TABLE .
                    "` AS m ON us.id = m.us_id " .
                    "INNER JOIN `ss_services` AS s ON us.service = s.id " .
                    "WHERE m.mybb_uid = '%d'",
                [$userService->getMybbUid()]
            )
        );

        foreach ($result as $row) {
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
                    $this::USER_SERVICE_TABLE .
                    "` " .
                    "WHERE `service` = '%s' AND `mybb_uid` = '%d'",
                [$this->service->getId(), $mybbUid]
            )
        );

        if ($result->rowCount()) {
            // Aktualizujemy
            $row = $result->fetch();
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
                    "INSERT INTO `ss_user_service` (`uid`, `service`, `expire`) " .
                        "VALUES ('%d', '%s', IF('%d' = '1', '-1', UNIX_TIMESTAMP() + '%d')) ",
                    [$uid, $this->service->getId(), $forever, $days * 24 * 60 * 60]
                )
            );
            $userServiceId = $this->db->lastId();

            $this->db->query(
                $this->db->prepare(
                    "INSERT INTO `" .
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
        return $this->template->renderNoComments(
            "services/mybb_extra_groups/user_service_admin_add",
            ['moduleId' => $this->getModuleId()]
        );
    }

    public function userServiceAdminAdd(array $body)
    {
        $user = $this->auth->user();
        $forever = (bool) array_get($body, 'forever');

        $this->connectMybb();

        $validator = new Validator(
            array_merge($body, [
                'quantity' => as_int(array_get($body, 'quantity')),
            ]),
            [
                'quantity' => $forever
                    ? []
                    : [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
                'uid' => [new UserExistsRule()],
                'mybb_username' => [new RequiredRule(), new MybbUserExistsRule($this->dbMybb)],
                'email' => [new EmailRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        // Add payment info
        $paymentId = $this->adminPaymentService->payByAdmin($user);

        $purchase = new Purchase($this->heart->getUser($validated['uid']));
        $purchase->setService($this->service->getId());
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_ADMIN,
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $purchase->setOrder([
            'username' => $validated['mybb_username'],
            Purchase::ORDER_QUANTITY => $validated['quantity'],
            Purchase::ORDER_FOREVER => $forever,
        ]);
        $purchase->setEmail($validated['email']);
        $boughtServiceId = $this->purchase($purchase);

        $this->logger->logWithActor(
            'log_user_service_added',
            $user->getUsername(),
            $user->getUid(),
            $boughtServiceId
        );
    }

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit)
    {
        if (!($userService instanceof MybbExtraGroupsUserService)) {
            throw new UnexpectedValueException();
        }

        $this->connectMybb();

        $statement = $this->dbMybb->statement(
            "SELECT `username` FROM `mybb_users` WHERE `uid` = ?"
        );
        $statement->execute([$userService->getMybbUid()]);
        $username = $statement->fetchColumn();

        $expire = $userService->isForever()
            ? $this->lang->t('never')
            : convert_date($userService->getExpire());
        $mybbUid = "$username ({$userService->getMybbUid()})";

        return $this->template->render(
            "services/mybb_extra_groups/user_own_service",
            compact('mybbUid', 'expire') + [
                'moduleId' => $this->getModuleId(),
                'serviceName' => $this->service->getName(),
                'userServiceId' => $userService->getId(),
            ]
        );
    }

    /**
     * @param string|int $userId Int - by uid, String - by username
     * @return null|MybbUser
     */
    private function findMybbUser($userId)
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

        if (!$result->rowCount()) {
            return null;
        }

        $rowMybb = $result->fetch();

        $mybbUser = new MybbUser($rowMybb['uid'], $rowMybb['usergroup']);
        $mybbUser->setMybbAddGroups(explode(",", $rowMybb['additionalgroups']));
        $mybbUser->setMybbDisplayGroup($rowMybb['displaygroup']);

        $result = $this->db->query(
            $this->db->prepare(
                "SELECT `gid`, UNIX_TIMESTAMP(`expire`) - UNIX_TIMESTAMP() AS `expire`, `was_before` FROM `ss_mybb_user_group` " .
                    "WHERE `uid` = '%d'",
                [$rowMybb['uid']]
            )
        );

        foreach ($result as $row) {
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
            $this->db->prepare("DELETE FROM `ss_mybb_user_group` WHERE `uid` = '%d'", [
                $mybbUser->getUid(),
            ])
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
                "INSERT INTO `ss_mybb_user_group` (`uid`, `gid`, `expire`, `was_before`) " .
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
