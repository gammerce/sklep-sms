<?php
namespace App\ServiceModules\MybbExtraGroups;

use App\Exceptions\InvalidConfigException;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\IntegerCommaSeparatedListRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\MybbUserExistsRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Rules\YesNoRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Managers\UserManager;
use App\Models\MybbExtraGroupsUserService;
use App\Models\MybbUser;
use App\Models\Purchase;
use App\Models\QuantityPrice;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserService;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PurchasePriceService;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\Services\PriceTextService;
use App\Support\QueryParticle;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\ExpirationCell;
use App\View\Html\HeadCell;
use App\View\Html\NoneText;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use App\View\Renders\PurchasePriceRenderer;
use Exception;
use PDOException;
use UnexpectedValueException;

class MybbExtraGroupsServiceModule extends ServiceModule implements
    IServiceAdminManage,
    IServiceCreate,
    IServiceUserServiceAdminDisplay,
    IServicePurchaseWeb,
    IServiceUserServiceAdminAdd,
    IServiceUserOwnServices,
    IServicePromoCode
{
    const MODULE_ID = "mybb_extra_groups";
    const USER_SERVICE_TABLE = "ss_user_service_mybb_extra_groups";

    /** @var array */
    private $groups = [];

    /** @var string */
    private $dbHost;

    /** @var int */
    private $dbPort;

    /** @var string */
    private $dbUser;

    /** @var string */
    private $dbPassword;

    /** @var string */
    private $dbName;

    /** @var Auth */
    private $auth;

    /** @var UserManager */
    private $userManager;

    /** @var Translator */
    private $lang;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var AdminPaymentService */
    private $adminPaymentService;

    /** @var PurchasePriceService */
    private $purchasePriceService;

    /** @var PurchasePriceRenderer */
    private $purchasePriceRenderer;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var MybbRepository */
    private $mybbRepository;

    /** @var MybbUserGroupRepository */
    private $mybbUserGroupRepository;

    /** @var MybbUserServiceRepository */
    private $mybbUserServiceRepository;

    /** @var DatabaseLogger */
    private $logger;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->auth = $this->app->make(Auth::class);
        $this->userManager = $this->app->make(UserManager::class);
        $this->mybbUserGroupRepository = $this->app->make(MybbUserGroupRepository::class);
        $this->mybbUserServiceRepository = $this->app->make(MybbUserServiceRepository::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
        $this->adminPaymentService = $this->app->make(AdminPaymentService::class);
        $this->purchasePriceService = $this->app->make(PurchasePriceService::class);
        $this->purchasePriceRenderer = $this->app->make(PurchasePriceRenderer::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();

        $this->readServiceData();

        /** @var MybbRepositoryFactory $mybbRepositoryFactory */
        $mybbRepositoryFactory = $this->app->make(MybbRepositoryFactory::class);
        $this->mybbRepository = $mybbRepositoryFactory->create(
            $this->dbHost,
            $this->dbPort,
            $this->dbUser,
            $this->dbPassword,
            $this->dbName
        );
    }

    private function readServiceData()
    {
        $serviceData = $this->service ? $this->service->getData() : null;
        if (isset($serviceData["mybb_groups"])) {
            $this->groups = explode(",", $serviceData["mybb_groups"]);
        }
        $this->dbHost = array_get($serviceData, "db_host", "");
        $this->dbPort = array_get($serviceData, "db_port", 3306);
        $this->dbUser = array_get($serviceData, "db_user", "");
        $this->dbPassword = array_get($serviceData, "db_password", "");
        $this->dbName = array_get($serviceData, "db_name", "");
    }

    /**
     * @param array $data
     * @return MybbExtraGroupsUserService
     */
    public function mapToUserService(array $data)
    {
        return new MybbExtraGroupsUserService(
            as_int($data["id"]),
            as_string($data["service_id"]),
            as_int($data["user_id"]),
            as_int($data["expire"]),
            as_int($data["mybb_uid"])
        );
    }

    public function serviceAdminExtraFieldsGet()
    {
        if ($this->showOnWeb()) {
            $webSelYes = "selected";
            $webSelNo = "";
        } else {
            $webSelYes = "";
            $webSelNo = "selected";
        }

        return $this->template->renderNoComments("admin/services/mybb_extra_groups/extra_fields", [
            "moduleId" => $this->getModuleId(),
            "webSelYes" => $webSelYes,
            "webSelNo" => $webSelNo,
            "mybbGroups" => implode(",", $this->groups),
            "dbHost" => $this->dbHost,
            "dbUser" => $this->dbUser,
            "dbPassword" => strlen($this->dbPassword) ? "********" : "",
            "dbName" => $this->dbName,
        ]);
    }

    public function serviceAdminManagePre(Validator $validator)
    {
        $validator->extendRules([
            "db_host" => [new RequiredRule()],
            "db_user" => [new RequiredRule()],
            "db_password" => [],
            "db_name" => [new RequiredRule()],
            "mybb_groups" => [new RequiredRule(), new IntegerCommaSeparatedListRule()],
            "web" => [new RequiredRule(), new YesNoRule()],
        ]);
    }

    public function serviceAdminManagePost(array $body)
    {
        $mybbGroups = explode(",", $body["mybb_groups"]);
        foreach ($mybbGroups as $key => $group) {
            $mybbGroups[$key] = trim($group);
            if (!strlen($mybbGroups[$key])) {
                unset($mybbGroups[$key]);
            }
        }

        $extraData = [
            "mybb_groups" => implode(",", $mybbGroups),
            "web" => $body["web"],
            "db_host" => $body["db_host"],
            "db_user" => $body["db_user"],
            "db_password" => array_get($body, "db_password", $this->dbPassword),
            "db_name" => $body["db_name"],
        ];

        return [
            "data" => $extraData,
        ];
    }

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->t("mybb_groups");
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);

        $queryParticle = new QueryParticle();

        if (isset($query["search"])) {
            $queryParticle->extend(
                create_search_query(
                    ["us.id", "us.user_id", "u.username", "s.name", "usmeg.mybb_uid"],
                    $query["search"]
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle} ";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS us.id, us.user_id, u.username, " .
                "s.id AS `service_id`, s.name AS `service`, us.expire, usmeg.mybb_uid " .
                "FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS usmeg ON usmeg.us_id = us.id " .
                "LEFT JOIN `ss_services` AS s ON s.id = usmeg.service_id " .
                "LEFT JOIN `ss_users` AS u ON u.uid = us.user_id " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(
            array_merge($queryParticle->params(), get_row_limit($currentPage->getPageNumber()))
        );
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $userEntry = $row["user_id"]
                    ? new UserRef($row["user_id"], $row["username"])
                    : new NoneText();

                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($userEntry))
                    ->addCell(new Cell($row["service"]))
                    ->addCell(new Cell($row["mybb_uid"]))
                    ->addCell(new ExpirationCell($row["expire"]))
                    ->setDeleteAction(has_privileges("manage_user_services"))
                    ->setEditAction(false);
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("user")))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(new HeadCell($this->lang->t("mybb_user")))
            ->addHeadCell(new HeadCell($this->lang->t("expires")))
            ->addBodyRows($bodyRows)
            ->enablePagination("/admin/user_service", $query, $rowsCount);

        return (new Wrapper())->enableSearch()->setTable($table);
    }

    public function purchaseFormGet(array $query)
    {
        $quantities = collect($this->purchasePriceService->getServicePrices($this->service))
            ->map(function (QuantityPrice $price) {
                return $this->purchasePriceRenderer->render($price, $this->service);
            })
            ->join();

        $costBox = $this->template->render("shop/components/purchase/cost_box");

        return $this->template->render("shop/services/mybb_extra_groups/purchase_form", [
            "costBox" => $costBox,
            "quantities" => $quantities,
            "serviceId" => $this->service->getId(),
            "user" => $this->auth->user(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        $validator = new Validator(
            [
                "email" => array_get($body, "email"),
                "quantity" => as_int(array_get($body, "quantity")),
                "username" => array_get($body, "username"),
            ],
            [
                "email" => [new RequiredRule(), new EmailRule()],
                "quantity" => [new IntegerRule()],
                "username" => [new RequiredRule(), new MybbUserExistsRule($this->mybbRepository)],
            ]
        );

        $validated = $validator->validateOrFail();
        $quantity = $validated["quantity"] === -1 ? null : $validated["quantity"];

        $purchase->setOrder([
            "username" => $validated["username"],
        ]);
        $purchase->setEmail($validated["email"]);

        $quantityPrice = $this->purchasePriceService->getServicePriceByQuantity(
            $quantity,
            $this->service
        );

        if ($quantityPrice) {
            $purchase->setPayment([
                Purchase::PAYMENT_PRICE_SMS => $quantityPrice->smsPrice,
                Purchase::PAYMENT_PRICE_TRANSFER => $quantityPrice->transferPrice,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => $quantityPrice->directBillingPrice,
            ]);
        }
    }

    public function orderDetails(Purchase $purchase)
    {
        $email = $purchase->getEmail() ?: $this->lang->t("none");
        $username = $purchase->getOrder("username");
        $serviceName = $this->service->getNameI18n();
        $quantity =
            $purchase->getOrder(Purchase::ORDER_QUANTITY) === null
                ? $this->lang->t("forever")
                : $purchase->getOrder(Purchase::ORDER_QUANTITY) . " " . $this->service->getTag();

        return $this->template->renderNoComments(
            "shop/services/mybb_extra_groups/order_details",
            compact("quantity", "username", "email", "serviceName")
        );
    }

    public function purchase(Purchase $purchase)
    {
        $mybbUser = $this->findMybbUser($purchase->getOrder("username"));

        // Nie znaleziono użytkownika o takich danych jak podane podczas zakupu
        if (!$mybbUser) {
            $this->logger->log(
                "log_mybb_purchase_no_user",
                json_encode($purchase->getPaymentList())
            );
            throw new Exception("User was deleted from MyBB db during the purchase.");
        }

        $quantity = multiply($purchase->getOrder(Purchase::ORDER_QUANTITY), 24 * 60 * 60);

        $this->mybbUserServiceRepository->create(
            $this->service->getId(),
            $purchase->user->getId(),
            $quantity,
            $mybbUser->getUid()
        );

        foreach ($this->groups as $group) {
            $mybbUser->prolongShopGroup($group, $quantity);
        }
        $this->saveMybbUser($mybbUser);

        $promoCode = $purchase->getPromoCode();

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder("username") . " ({$mybbUser->getUid()})",
            $purchase->getEmail(),
            $promoCode ? $promoCode->getCode() : null,
            [
                "uid" => $mybbUser->getUid(),
                "groups" => implode(",", $this->groups),
            ]
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $username = $transaction->getAuthData();
        $quantity = $transaction->isForever()
            ? $this->lang->t("forever")
            : $transaction->getQuantity() . " " . $this->service->getTag();
        $cost = $transaction->getCost()
            ? $this->priceTextService->getPriceText($transaction->getCost())
            : $this->lang->t("none");

        if ($action === "email") {
            return $this->template->renderNoComments(
                "shop/services/mybb_extra_groups/purchase_info_email",
                compact("username", "quantity", "cost") + [
                    "serviceName" => $this->service->getNameI18n(),
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/mybb_extra_groups/purchase_info_web",
                compact("cost", "username", "quantity") + [
                    "email" => $transaction->getEmail(),
                    "serviceName" => $this->service->getNameI18n(),
                ]
            );
        }

        if ($action === "payment_log") {
            return [
                "text" => $this->lang->t(
                    "mybb_group_bought",
                    $this->service->getNameI18n(),
                    $username
                ),
                "class" => "outcome",
            ];
        }

        return "";
    }

    public function userServiceDelete(UserService $userService, $who)
    {
        try {
            $this->mybbRepository->connectDb();
            return true;
        } catch (PDOException $e) {
            if ($who === "admin") {
                throw new InvalidConfigException($e->getMessage());
            }

            return false;
        }
    }

    public function userServiceDeletePost(UserService $userService)
    {
        if (!($userService instanceof MybbExtraGroupsUserService)) {
            throw new UnexpectedValueException();
        }

        $mybbUser = $this->findMybbUser($userService->getMybbUid());

        // Usuwamy wszystkie shopGroups oraz z mybbGroups te grupy, które maja was_before = false
        foreach ($mybbUser->getShopGroup() as $gid => $groupData) {
            if (!$groupData["was_before"]) {
                $mybbUser->removeMybbAddGroup($gid);
            }
        }
        $mybbUser->removeShopGroup();

        // Dodajemy uzytkownikowi grupy na podstawie USER_SERVICE_TABLE
        $statement = $this->db->statement(
            "SELECT us.expire - UNIX_TIMESTAMP() AS `expire`, s.data AS `extra_data` FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS m ON us.id = m.us_id " .
                "INNER JOIN `ss_services` AS s ON us.service_id = s.id " .
                "WHERE m.mybb_uid = ?"
        );
        $statement->execute([$userService->getMybbUid()]);

        foreach ($statement as $row) {
            $row["extra_data"] = json_decode($row["extra_data"], true);
            foreach (explode(",", $row["extra_data"]["mybb_groups"]) as $groupId) {
                $mybbUser->prolongShopGroup($groupId, $row["expire"]);
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
     * Metoda powinna zwrócić dodatkowe pola do uzupełnienia przez admina
     * podczas dodawania usługi użytkownikowi
     *
     * @return string
     */
    public function userServiceAdminAddFormGet()
    {
        return $this->template->renderNoComments(
            "admin/services/mybb_extra_groups/user_service_admin_add",
            ["moduleId" => $this->getModuleId()]
        );
    }

    public function userServiceAdminAdd(array $body)
    {
        $user = $this->auth->user();
        $forever = (bool) array_get($body, "forever");

        $validator = new Validator(
            array_merge($body, [
                "quantity" => as_int(array_get($body, "quantity")),
            ]),
            [
                "quantity" => $forever
                    ? []
                    : [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
                "user_id" => [new UserExistsRule()],
                "mybb_username" => [
                    new RequiredRule(),
                    new MybbUserExistsRule($this->mybbRepository),
                ],
                "email" => [new EmailRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        // Add payment info
        $paymentId = $this->adminPaymentService->payByAdmin($user);

        $purchase = (new Purchase($this->userManager->getUser($validated["user_id"])))
            ->setServiceId($this->service->getId())
            ->setPaymentOption(new PaymentOption(PaymentMethod::ADMIN()))
            ->setPayment([
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ])
            ->setOrder([
                "username" => $validated["mybb_username"],
                Purchase::ORDER_QUANTITY => $forever ? null : $validated["quantity"],
            ])
            ->setEmail($validated["email"]);

        $boughtServiceId = $this->purchase($purchase);
        $this->logger->logWithActor(
            "log_user_service_added",
            $user->getUsername(),
            $user->getId(),
            $boughtServiceId
        );
    }

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit)
    {
        if (!($userService instanceof MybbExtraGroupsUserService)) {
            throw new UnexpectedValueException();
        }

        $username = $this->mybbRepository->findUsernameByUid($userService->getMybbUid());

        return $this->template->render(
            "shop/services/mybb_extra_groups/user_own_service",
            compact("mybbUid", "expire") + [
                "expire" => as_expiration_datetime_string($userService->getExpire()),
                "moduleId" => $this->getModuleId(),
                "mybbUid" => "$username ({$userService->getMybbUid()})",
                "serviceName" => $this->service->getNameI18n(),
                "userServiceId" => $userService->getId(),
            ]
        );
    }

    /**
     * @param string|int $userId Int - by uid, String - by username
     * @return MybbUser|null
     */
    private function findMybbUser($userId)
    {
        if (is_integer($userId)) {
            $rawMybbUser = $this->mybbRepository->getUserByUid($userId);
        } else {
            $rawMybbUser = $this->mybbRepository->getUserByUsername($userId);
        }

        if (!$rawMybbUser) {
            return null;
        }

        $mybbUser = new MybbUser($rawMybbUser["uid"], $rawMybbUser["usergroup"]);
        $mybbUser->setMybbAddGroups(explode(",", $rawMybbUser["additionalgroups"]));
        $mybbUser->setMybbDisplayGroup($rawMybbUser["displaygroup"]);

        $rows = $this->mybbUserGroupRepository->findGroupsExpiration($rawMybbUser["uid"]);

        foreach ($rows as $row) {
            $mybbUser->setShopGroup($row["gid"], [
                "expire" => $row["expire"],
                "was_before" => $row["was_before"],
            ]);
        }

        return $mybbUser;
    }

    /**
     * Zapisuje dane o użytkowniku
     *
     * @param MybbUser $mybbUser
     */
    private function saveMybbUser(MybbUser $mybbUser)
    {
        $this->mybbRepository->connectDb();

        $this->mybbUserGroupRepository->delete($mybbUser->getUid());

        $this->mybbUserGroupRepository->createMany(
            collect($mybbUser->getShopGroup())
                ->map(function (array $groupData, $groupId) use ($mybbUser) {
                    return [
                        "uid" => $mybbUser->getUid(),
                        "gid" => $groupId,
                        "expire" => $groupData["expire"],
                        "was_before" => $groupData["was_before"],
                    ];
                })
                ->all()
        );

        $additionalGroups = collect($mybbUser->getMybbAddGroups())
            ->extend(array_keys($mybbUser->getShopGroup()))
            ->unique()
            ->values()
            ->sort()
            ->all();

        $this->mybbRepository->updateGroups(
            $mybbUser->getUid(),
            $additionalGroups,
            $mybbUser->getMybbDisplayGroup()
        );
    }
}
