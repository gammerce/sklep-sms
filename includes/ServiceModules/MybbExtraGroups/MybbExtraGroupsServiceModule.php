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
use App\Service\ServiceDescriptionService;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\Support\Database;
use App\Support\PriceTextService;
use App\Support\QueryParticle;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\ExpirationCell;
use App\View\Html\HeadCell;
use App\View\Html\NoneText;
use App\View\Html\PreWrapCell;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use App\View\Pagination\PaginationFactory;
use App\View\Renders\PurchasePriceRenderer;
use Exception;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

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

    /** @var string[] */
    private array $groups = [];

    private string $dbHost;
    private int $dbPort;
    private string $dbUser;
    private string $dbPassword;
    private string $dbName;

    private Auth $auth;
    private UserManager $userManager;
    private Translator $lang;
    private BoughtServiceService $boughtServiceService;
    private AdminPaymentService $adminPaymentService;
    private PurchasePriceService $purchasePriceService;
    private PurchasePriceRenderer $purchasePriceRenderer;
    private PriceTextService $priceTextService;
    private MybbRepository $mybbRepository;
    private MybbUserGroupRepository $mybbUserGroupRepository;
    private MybbUserServiceRepository $mybbUserServiceRepository;
    private DatabaseLogger $logger;
    private PaginationFactory $paginationFactory;
    private Database $db;

    public function __construct(
        AdminPaymentService $adminPaymentService,
        Auth $auth,
        BoughtServiceService $boughtServiceService,
        Database $db,
        DatabaseLogger $logger,
        MybbRepositoryFactory $mybbRepositoryFactory,
        MybbUserGroupRepository $mybbUserGroupRepository,
        MybbUserServiceRepository $mybbUserServiceRepository,
        PaginationFactory $paginationFactory,
        PriceTextService $priceTextService,
        PurchasePriceRenderer $purchasePriceRenderer,
        PurchasePriceService $purchasePriceService,
        ServiceDescriptionService $serviceDescriptionService,
        Template $template,
        TranslationManager $translationManager,
        UserManager $userManager,
        ?Service $service = null
    ) {
        parent::__construct($template, $serviceDescriptionService, $service);
        $this->adminPaymentService = $adminPaymentService;
        $this->auth = $auth;
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->logger = $logger;
        $this->mybbUserGroupRepository = $mybbUserGroupRepository;
        $this->mybbUserServiceRepository = $mybbUserServiceRepository;
        $this->paginationFactory = $paginationFactory;
        $this->priceTextService = $priceTextService;
        $this->purchasePriceRenderer = $purchasePriceRenderer;
        $this->purchasePriceService = $purchasePriceService;
        $this->userManager = $userManager;
        $this->lang = $translationManager->user();

        $this->readServiceData();

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

    public function mapToUserService(array $data): MybbUserService
    {
        return $this->mybbUserServiceRepository->mapToModel($data);
    }

    public function serviceAdminExtraFieldsGet(): string
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

    public function serviceAdminManagePre(Validator $validator): void
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

    public function serviceAdminManagePost(array $body): array
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

    public function userServiceAdminDisplayTitleGet(): string
    {
        return $this->lang->t("mybb_groups");
    }

    public function userServiceAdminDisplayGet(Request $request): Wrapper
    {
        $pagination = $this->paginationFactory->create($request);
        $queryParticle = new QueryParticle();

        if ($request->query->has("search")) {
            $queryParticle->extend(
                create_search_query(
                    ["us.id", "us.user_id", "u.username", "s.name", "usmeg.mybb_uid"],
                    $request->query->get("search")
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle} ";

        $statement = $this->db->statement(
            <<<EOF
            SELECT 
            SQL_CALC_FOUND_ROWS 
            us.id,
            us.user_id,
            us.comment,
            u.username, 
            s.id AS `service_id`,
            s.name AS `service`,
            us.expire,
            usmeg.mybb_uid 
            FROM `ss_user_service` AS us 
            INNER JOIN `{$this->getUserServiceTable()}` AS usmeg ON usmeg.us_id = us.id 
            LEFT JOIN `ss_services` AS s ON s.id = usmeg.service_id 
            LEFT JOIN `ss_users` AS u ON u.uid = us.user_id 
            {$where}
            ORDER BY us.id DESC 
            LIMIT ?, ?
            EOF
        );
        $statement->execute(array_merge($queryParticle->params(), $pagination->getSqlLimit()));
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
                    ->addCell(new PreWrapCell($row["comment"]))
                    ->setDeleteAction(can(Permission::MANAGE_USER_SERVICES()))
                    ->setEditAction(false);
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("user")))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(new HeadCell($this->lang->t("mybb_user")))
            ->addHeadCell(new HeadCell($this->lang->t("expires")))
            ->addHeadCell(new HeadCell($this->lang->t("comment")))
            ->addBodyRows($bodyRows)
            ->enablePagination("/admin/user_service", $pagination, $rowsCount);

        return (new Wrapper())->enableSearch()->setTable($table);
    }

    public function purchaseFormGet(array $query): string
    {
        $quantities = collect($this->purchasePriceService->getServicePrices($this->service))
            ->map(
                fn(QuantityPrice $price) => $this->purchasePriceRenderer->render(
                    $price,
                    $this->service
                )
            )
            ->join();

        $costBox = $this->template->render("shop/components/purchase/cost_box");

        return $this->template->render("shop/services/mybb_extra_groups/purchase_form", [
            "costBox" => $costBox,
            "quantities" => $quantities,
            "serviceId" => $this->service->getId(),
            "user" => $this->auth->user(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body): void
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
                Purchase::PAYMENT_PRICE_SMS => as_int($quantityPrice->smsPrice),
                Purchase::PAYMENT_PRICE_TRANSFER => as_int($quantityPrice->transferPrice),
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => as_int(
                    $quantityPrice->directBillingPrice
                ),
            ]);
        }
    }

    public function orderDetails(Purchase $purchase): string
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

    public function purchase(Purchase $purchase): int
    {
        $mybbUser = $this->findMybbUser($purchase->getOrder("username"));

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
            $mybbUser->getUid(),
            $purchase->getComment()
        );

        foreach ($this->groups as $group) {
            $mybbUser->prolongShopGroup($group, $quantity);
        }
        $this->saveMybbUser($mybbUser);

        $promoCode = $purchase->getPromoCode();

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->getAddressIp(),
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
        $cost =
            $this->priceTextService->getPriceText($transaction->getCost()) ?:
            $this->lang->t("none");

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

    public function userServiceDelete(UserService $userService, $who): bool
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

    public function userServiceDeletePost(UserService $userService): void
    {
        assert($userService instanceof MybbUserService);

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

    public function userServiceAdminAddFormGet(): string
    {
        return $this->template->renderNoComments(
            "admin/services/mybb_extra_groups/user_service_admin_add",
            ["moduleId" => $this->getModuleId()]
        );
    }

    public function userServiceAdminAdd(Request $request): int
    {
        $admin = $this->auth->user();
        $forever = (bool) $request->request->get("forever");

        $validator = new Validator(
            array_merge($request->request->all(), [
                "quantity" => as_int($request->request->get("quantity")),
            ]),
            [
                "comment" => [],
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
        $user = $this->userManager->get($validated["user_id"]);

        // Add payment info
        $paymentId = $this->adminPaymentService->payByAdmin(
            $admin,
            get_ip($request),
            get_platform($request)
        );

        $purchase = (new Purchase($user, get_ip($request), get_platform($request)))
            ->setServiceId($this->service->getId())
            ->setPaymentOption(new PaymentOption(PaymentMethod::ADMIN()))
            ->setPayment([
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ])
            ->setOrder([
                "username" => $validated["mybb_username"],
                Purchase::ORDER_QUANTITY => $forever ? null : $validated["quantity"],
            ])
            ->setEmail($validated["email"])
            ->setComment($validated["comment"]);

        return $this->purchase($purchase);
    }

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit): string
    {
        assert($userService instanceof MybbUserService);

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
    private function findMybbUser($userId): ?MybbUser
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
                ->map(
                    fn(array $groupData, $groupId) => [
                        "uid" => $mybbUser->getUid(),
                        "gid" => $groupId,
                        "expire" => $groupData["expire"],
                        "was_before" => $groupData["was_before"],
                    ]
                )
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
