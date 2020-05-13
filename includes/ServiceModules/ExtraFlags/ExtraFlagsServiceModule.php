<?php
namespace App\ServiceModules\ExtraFlags;

use App\Exceptions\ValidationException;
use App\Http\Validation\Rules\ConfirmedRule;
use App\Http\Validation\Rules\DateTimeRule;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\MaxLengthRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\ServerExistsRule;
use App\Http\Validation\Rules\ServerLinkedToServiceRule;
use App\Http\Validation\Rules\UniqueFlagsRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Rules\YesNoRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Managers\ServerManager;
use App\Managers\ServerServiceManager;
use App\Managers\ServiceManager;
use App\Managers\ServiceModuleManager;
use App\Managers\UserManager;
use App\Models\Purchase;
use App\Models\QuantityPrice;
use App\Models\Server;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserService;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PurchasePriceService;
use App\Payment\General\ServiceTakeOverFactory;
use App\Repositories\UserServiceRepository;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagAuthDataRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagPasswordDiffersRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagPasswordRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagServiceTypesRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagTypeListRule;
use App\ServiceModules\ExtraFlags\Rules\ExtraFlagTypeRule;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminEdit;
use App\ServiceModules\ServiceModule;
use App\Services\ExpiredUserServiceService;
use App\Services\PriceTextService;
use App\Support\Expression;
use App\Support\QueryParticle;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\CurrentPage;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\ExpirationCell;
use App\View\Html\HeadCell;
use App\View\Html\ServerRef;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use App\View\Renders\PurchasePriceRenderer;
use InvalidArgumentException;
use UnexpectedValueException;

class ExtraFlagsServiceModule extends ServiceModule implements
    IServiceAdminManage,
    IServiceCreate,
    IServicePurchaseExternal,
    IServiceUserServiceAdminDisplay,
    IServicePurchaseWeb,
    IServiceUserServiceAdminAdd,
    IServiceUserServiceAdminEdit,
    IServiceActionExecute,
    IServiceUserOwnServices,
    IServiceUserOwnServicesEdit,
    IServiceTakeOver
{
    const MODULE_ID = "extra_flags";
    const USER_SERVICE_TABLE = "ss_user_service_extra_flags";

    /** @var Translator */
    private $lang;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    /** @var ServerManager */
    private $serverManager;

    /** @var ServerServiceManager */
    private $serverServiceManager;

    /** @var ServiceManager */
    private $serviceManager;

    /** @var UserManager */
    private $userManager;

    /** @var Auth */
    private $auth;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var DatabaseLogger */
    private $logger;

    /** @var ExpiredUserServiceService */
    private $expiredUserServiceService;

    /** @var AdminPaymentService */
    private $adminPaymentService;

    /** @var PurchasePriceService */
    private $purchasePriceService;

    /** @var PurchasePriceRenderer */
    private $purchasePriceRenderer;

    /** @var UserServiceRepository */
    private $userServiceRepository;

    /** @var ExtraFlagUserServiceRepository */
    private $extraFlagUserServiceRepository;

    /** @var PlayerFlagRepository */
    private $playerFlagRepository;

    /** @var PlayerFlagService */
    private $playerFlagService;

    /** @var PriceTextService */
    private $priceTextService;

    /** @var ServiceTakeOverFactory */
    private $serviceTakeOverFactory;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->auth = $this->app->make(Auth::class);
        $this->serviceModuleManager = $this->app->make(ServiceModuleManager::class);
        $this->serverManager = $this->app->make(ServerManager::class);
        $this->serverServiceManager = $this->app->make(ServerServiceManager::class);
        $this->serviceManager = $this->app->make(ServiceManager::class);
        $this->userManager = $this->app->make(UserManager::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
        $this->expiredUserServiceService = $this->app->make(ExpiredUserServiceService::class);
        $this->adminPaymentService = $this->app->make(AdminPaymentService::class);
        $this->purchasePriceService = $this->app->make(PurchasePriceService::class);
        $this->purchasePriceRenderer = $this->app->make(PurchasePriceRenderer::class);
        $this->extraFlagUserServiceRepository = $this->app->make(
            ExtraFlagUserServiceRepository::class
        );
        $this->userServiceRepository = $this->app->make(UserServiceRepository::class);
        $this->playerFlagRepository = $this->app->make(PlayerFlagRepository::class);
        $this->playerFlagService = $this->app->make(PlayerFlagService::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
        $this->serviceTakeOverFactory = $this->app->make(ServiceTakeOverFactory::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    /**
     * @param array $data
     * @return ExtraFlagUserService
     */
    public function mapToUserService(array $data)
    {
        return $this->extraFlagUserServiceRepository->mapToModel($data);
    }

    public function serviceAdminExtraFieldsGet()
    {
        // WEB
        $webSelYes = $this->showOnWeb() ? "selected" : "";
        $webSelNo = $this->showOnWeb() ? "" : "selected";

        $types = $this->getTypeOptions(
            ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP | ExtraFlagType::TYPE_SID,
            $this->service ? $this->service->getTypes() : 0
        );

        // Pobieramy flagi, jeżeli service nie jest puste
        // czyli kiedy edytujemy, a nie dodajemy usługę
        $flags = $this->service ? $this->service->getFlags() : "";

        return $this->template->renderNoComments(
            "admin/services/extra_flags/extra_fields",
            compact("webSelNo", "webSelYes", "types", "flags") + [
                "moduleId" => $this->getModuleId(),
            ]
        );
    }

    public function serviceAdminManagePre(Validator $validator)
    {
        $validator->extendRules([
            "flags" => [new RequiredRule(), new MaxLengthRule(25), new UniqueFlagsRule()],
            "type" => [new RequiredRule(), new ExtraFlagTypeListRule()],
            "web" => [new RequiredRule(), new YesNoRule()],
        ]);
    }

    public function serviceAdminManagePost(array $body)
    {
        // Przygotowujemy do zapisu ( suma bitowa ), które typy zostały wybrane
        $types = 0;
        foreach ($body["type"] as $type) {
            $types |= $type;
        }

        $data = $this->service ? $this->service->getData() : [];
        $data["web"] = $body["web"];

        $this->serviceDescriptionService->create($body["id"]);

        return [
            "types" => $types,
            "flags" => $body["flags"],
            "data" => $data,
        ];
    }

    // ----------------------------------------------------------------------------------
    // ### Wyświetlanie usług użytkowników w PA

    public function userServiceAdminDisplayTitleGet()
    {
        return $this->lang->t("extra_flags");
    }

    public function userServiceAdminDisplayGet(array $query, array $body)
    {
        /** @var CurrentPage $currentPage */
        $currentPage = $this->app->make(CurrentPage::class);
        $pageNumber = $currentPage->getPageNumber();

        $queryParticle = new QueryParticle();

        if (isset($query["search"])) {
            $queryParticle->extend(
                create_search_query(
                    ["us.id", "us.user_id", "u.username", "srv.name", "s.name", "usef.auth_data"],
                    $query["search"]
                )
            );
        }

        $where = $queryParticle->isEmpty() ? "" : "WHERE {$queryParticle} ";

        $statement = $this->db->statement(
            "SELECT SQL_CALC_FOUND_ROWS " .
                "us.id AS `id`, us.user_id AS `user_id`, u.username AS `username`, " .
                "srv.id AS `server_id`, srv.name AS `server_name`, " .
                "s.id AS `service_id`, s.name AS `service_name`, " .
                "usef.type AS `type`, usef.auth_data AS `auth_data`, us.expire AS `expire` " .
                "FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS usef ON usef.us_id = us.id " .
                "LEFT JOIN `ss_services` AS s ON s.id = usef.service_id " .
                "LEFT JOIN `ss_servers` AS srv ON srv.id = usef.server_id " .
                "LEFT JOIN `ss_users` AS u ON u.uid = us.user_id " .
                $where .
                "ORDER BY us.id DESC " .
                "LIMIT ?, ?"
        );
        $statement->execute(array_merge($queryParticle->params(), get_row_limit($pageNumber)));
        $rowsCount = $this->db->query("SELECT FOUND_ROWS()")->fetchColumn();

        $bodyRows = collect($statement)
            ->map(function (array $row) {
                $userEntry = $row["user_id"]
                    ? new UserRef($row["user_id"], $row["username"])
                    : $this->lang->t("none");

                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($userEntry))
                    ->addCell(new Cell(new ServerRef($row["server_id"], $row["server_name"])))
                    ->addCell(new Cell(new ServiceRef($row["service_id"], $row["service_name"])))
                    ->addCell(new Cell($row["auth_data"]))
                    ->addCell(new ExpirationCell($row["expire"]))
                    ->setDeleteAction(has_privileges("manage_user_services"))
                    ->setEditAction(has_privileges("manage_user_services"));
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("user")))
            ->addHeadCell(new HeadCell($this->lang->t("server")))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(
                new HeadCell(
                    "{$this->lang->t("nick")}/{$this->lang->t("ip")}/{$this->lang->t("sid")}"
                )
            )
            ->addHeadCell(new HeadCell($this->lang->t("expires")))
            ->addBodyRows($bodyRows)
            ->enablePagination("/admin/user_service", $query, $rowsCount);

        return (new Wrapper())->enableSearch()->setTable($table);
    }

    public function purchaseFormGet(array $query)
    {
        $types = collect(ExtraFlagType::ALL)
            ->filter(function ($type) {
                return $this->service->getTypes() & $type;
            })
            ->map(function ($value) {
                return $this->template->render("shop/services/extra_flags/service_type", [
                    "type" => ExtraFlagType::getTypeName($value),
                    "value" => $value,
                ]);
            })
            ->join();

        $servers = $this->getServerOptions();

        return $this->template->render("shop/services/extra_flags/purchase_form", [
            "servers" => $servers,
            "serviceId" => $this->service->getId(),
            "types" => $types,
            "user" => $this->auth->user(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        $quantity = as_int(array_get($body, "quantity"));
        $serverId = as_int(array_get($body, "server_id"));
        $type = as_int(array_get($body, "type"));
        $authData = trim(array_get($body, "auth_data"));
        $password = array_get($body, "password");
        $passwordRepeat = array_get($body, "password_repeat");
        $email = array_get($body, "email");

        $purchase->setEmail($email);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $serverId,
            "type" => $type,
            "auth_data" => $authData,
            "password" => $password,
            "passwordr" => $passwordRepeat,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $quantity === -1 ? null : $quantity,
        ]);

        $validator = $this->purchaseDataValidate($purchase);
        $validator->validateOrFail();

        $quantityPrice = $this->purchasePriceService->getServicePriceByQuantity(
            $quantity,
            $this->service,
            $this->serverManager->getServer($serverId)
        );

        if ($quantityPrice) {
            $purchase->setPayment([
                Purchase::PAYMENT_PRICE_SMS => $quantityPrice->smsPrice,
                Purchase::PAYMENT_PRICE_TRANSFER => $quantityPrice->transferPrice,
                Purchase::PAYMENT_PRICE_DIRECT_BILLING => $quantityPrice->directBillingPrice,
            ]);
        }
    }

    public function purchaseDataValidate(Purchase $purchase)
    {
        $server = $this->serverManager->getServer($purchase->getOrder(Purchase::ORDER_SERVER));

        if ($server) {
            if ($server->getSmsPlatformId()) {
                $purchase->setPayment([
                    Purchase::PAYMENT_PLATFORM_SMS => $server->getSmsPlatformId(),
                ]);
            }

            if ($server->getTransferPlatformId()) {
                $purchase->setPayment([
                    Purchase::PAYMENT_PLATFORM_TRANSFER => $server->getTransferPlatformId(),
                ]);
            }
        }

        return new Validator(
            [
                "auth_data" => $purchase->getOrder("auth_data"),
                "password" => $purchase->getOrder("password"),
                "password_repeat" => $purchase->getOrder("passwordr"),
                "email" => $purchase->getEmail(),
                "quantity" => $purchase->getOrder(Purchase::ORDER_QUANTITY),
                "server_id" => $purchase->getOrder(Purchase::ORDER_SERVER),
                "type" => $purchase->getOrder("type"),
            ],
            [
                "auth_data" => [new RequiredRule(), new ExtraFlagAuthDataRule()],
                "email" => [
                    is_server_platform($purchase->user->getPlatform()) ? null : new RequiredRule(),
                    new EmailRule(),
                ],
                "password" => [
                    new ExtraFlagPasswordRule(),
                    new PasswordRule(),
                    new ConfirmedRule(),
                    new ExtraFlagPasswordDiffersRule(),
                ],
                "quantity" => [],
                "server_id" => [
                    new RequiredRule(),
                    new ServerExistsRule(),
                    new ServerLinkedToServiceRule($this->service),
                ],
                "type" => [
                    new RequiredRule(),
                    new ExtraFlagTypeRule(),
                    new ExtraFlagServiceTypesRule($this->service),
                ],
            ]
        );
    }

    public function orderDetails(Purchase $purchase)
    {
        $server = $this->serverManager->getServer($purchase->getOrder(Purchase::ORDER_SERVER));
        $typeName = $this->getTypeName($purchase->getOrder("type"));

        $password = "";
        if (strlen($purchase->getOrder("password"))) {
            $password =
                "<strong>{$this->lang->t("password")}</strong>: " .
                htmlspecialchars($purchase->getOrder("password")) .
                "<br />";
        }

        $email = $purchase->getEmail() ?: $this->lang->t("none");
        $authData = $purchase->getOrder("auth_data");
        $serviceName = $this->service->getNameI18n();
        $serverName = $server->getName();
        $quantity =
            $purchase->getOrder(Purchase::ORDER_QUANTITY) === null
                ? $this->lang->t("forever")
                : $purchase->getOrder(Purchase::ORDER_QUANTITY) . " " . $this->service->getTag();

        return $this->template->renderNoComments(
            "shop/services/extra_flags/order_details",
            compact(
                "quantity",
                "typeName",
                "authData",
                "password",
                "email",
                "serviceName",
                "serverName"
            )
        );
    }

    public function purchase(Purchase $purchase)
    {
        $this->addPlayerFlags(
            $purchase->user->getId(),
            $purchase->getOrder("type"),
            $purchase->getOrder("auth_data"),
            $purchase->getOrder("password"),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder(Purchase::ORDER_SERVER)
        );

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            $purchase->getOrder(Purchase::ORDER_SERVER),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder("auth_data"),
            $purchase->getEmail(),
            [
                "type" => $purchase->getOrder("type"),
                "password" => $purchase->getOrder("password"),
            ]
        );
    }

    private function addPlayerFlags($userId, $type, $authData, $password, $days, $serverId)
    {
        $authData = trim($authData);
        $password = strlen($password) ? $password : "";

        // Let's delete expired data. Just in case, to avoid risk of conflicts.
        $this->expiredUserServiceService->deleteExpired();
        $this->playerFlagRepository->deleteOldFlags();

        // Let's add a user service. If there is service with the same data,
        // let's prolong the existing one.
        $statement = $this->db->statement(
            "SELECT * FROM `{$this->getUserServiceTable()}` " .
                "WHERE `service_id` = ? AND `server_id` = ? AND `type` = ? AND `auth_data` = ?"
        );
        $statement->execute([$this->service->getId(), $serverId, $type, $authData]);

        if ($statement->rowCount()) {
            $row = $statement->fetch();
            $userServiceId = $row["us_id"];

            if ($days === null) {
                $expire = null;
            } else {
                $seconds = $days * 24 * 60 * 60;
                $expire = new Expression("`expire` + {$seconds}");
            }

            $this->userServiceRepository->updateWithModule($this, $userServiceId, [
                "user_id" => $userId,
                "password" => $password,
                "expire" => $expire,
            ]);
        } else {
            $this->extraFlagUserServiceRepository->create(
                $this->service->getId(),
                $userId,
                $days !== null ? $days * 24 * 60 * 60 : null,
                $serverId,
                $type,
                $authData,
                $password
            );
        }

        // Let's set identical passwords for all services of that player on that server
        $this->db
            ->statement(
                "UPDATE `{$this->getUserServiceTable()}` " .
                    "SET `password` = ? " .
                    "WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ?"
            )
            ->execute([$password, $serverId, $type, $authData]);

        // Let's recalculate players flags since we've added new user service
        $this->playerFlagService->recalculatePlayerFlags($serverId, $type, $authData);
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $password = "";
        if (strlen($transaction->getExtraDatum("password"))) {
            $password =
                "<strong>{$this->lang->t("password")}</strong>: " .
                htmlspecialchars($transaction->getExtraDatum("password")) .
                "<br />";
        }

        $quantity =
            $transaction->getQuantity() != -1
                ? "{$transaction->getQuantity()} {$this->service->getTag()}"
                : $this->lang->t("forever");

        $cost = $transaction->getCost()
            ? $this->priceTextService->getPriceText($transaction->getCost())
            : $this->lang->t("none");

        $server = $this->serverManager->getServer($transaction->getServerId());

        $setinfo = "";
        if (
            $transaction->getExtraDatum("type") &
            (ExtraFlagType::TYPE_NICK | ExtraFlagType::TYPE_IP)
        ) {
            $setinfo = $this->lang->t("type_setinfo", $transaction->getExtraDatum("password"));
        }

        if ($action === "email") {
            return $this->template->renderNoComments(
                "shop/services/extra_flags/purchase_info_email",
                compact("quantity", "password", "setinfo") + [
                    "authData" => $transaction->getAuthData(),
                    "typeName" => $this->getTypeName($transaction->getExtraDatum("type")),
                    "serviceName" => $this->service->getNameI18n(),
                    "serverName" => $server ? $server->getName() : "n/a",
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/extra_flags/purchase_info_web",
                compact("cost", "quantity", "password", "setinfo") + [
                    "authData" => $transaction->getAuthData(),
                    "email" => $transaction->getEmail(),
                    "typeName" => $this->getTypeName($transaction->getExtraDatum("type")),
                    "serviceName" => $this->service->getNameI18n(),
                    "serverName" => $server ? $server->getName() : "n/a",
                ]
            );
        }

        if ($action === "payment_log") {
            return [
                "text" => $this->lang->t(
                    "service_was_bought",
                    $this->service->getNameI18n(),
                    $server->getName()
                ),
                "class" => "outcome",
            ];
        }

        return "";
    }

    // ----------------------------------------------------------------------------------
    // ### Zarządzanie usługami użytkowników przez admina

    public function userServiceAdminAddFormGet()
    {
        $types = $this->getTypeOptions($this->service->getTypes());
        $servers = $this->getServerOptions();

        return $this->template->renderNoComments(
            "admin/services/extra_flags/user_service_admin_add",
            compact("types", "servers") + ["moduleId" => $this->getModuleId()]
        );
    }

    public function userServiceAdminAdd(array $body)
    {
        $forever = (bool) array_get($body, "forever");

        $validator = new Validator(
            array_merge($body, [
                "quantity" => as_int(array_get($body, "quantity")),
                "server_id" => as_int(array_get($body, "server_id")),
                "user_id" => as_int(array_get($body, "user_id")),
            ]),
            [
                "email" => [new EmailRule()],
                "password" => [new ExtraFlagPasswordRule()],
                "quantity" => $forever
                    ? []
                    : [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
                "server_id" => [new RequiredRule(), new ServerExistsRule()],
                "user_id" => [new UserExistsRule()],
            ]
        );
        $this->verifyUserServiceData($validator);
        $validated = $validator->validateOrFail();

        $admin = $this->auth->user();
        $paymentId = $this->adminPaymentService->payByAdmin($admin);

        $purchasingUser = $this->userManager->getUser($validated["user_id"]);
        $purchase = new Purchase($purchasingUser);
        $purchase->setServiceId($this->service->getId());
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_ADMIN,
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_SERVER => $validated["server_id"],
            "type" => $validated["type"],
            "auth_data" => $validated["auth_data"],
            "password" => $validated["password"],
            Purchase::ORDER_QUANTITY => $forever ? null : $validated["quantity"],
        ]);
        $purchase->setEmail($validated["email"]);
        $boughtServiceId = $this->purchase($purchase);

        $this->logger->logWithActor("log_user_service_added", $boughtServiceId);
    }

    public function userServiceAdminEditFormGet(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $services = collect($this->serviceManager->getServices())
            ->filter(function (Service $service) {
                $serviceModule = $this->serviceModuleManager->getEmpty($service->getModule());

                // Usługę możemy zmienić tylko na taka, która korzysta z tego samego modułu.
                // Inaczej to nie ma sensu, lepiej ją usunąć i dodać nową
                return $serviceModule && $this->getModuleId() === $serviceModule->getModuleId();
            })
            ->map(function (Service $service) use ($userService) {
                return create_dom_element("option", $service->getNameI18n(), [
                    "value" => $service->getId(),
                    "selected" =>
                        $userService->getServiceId() === $service->getId() ? "selected" : "",
                ]);
            })
            ->join();

        $types = $this->getTypeOptions($this->service->getTypes(), $userService->getType());

        $classes = [
            "nick" => "is-hidden",
            "ip" => "is-hidden",
            "sid" => "is-hidden",
            "password" => "is-hidden",
        ];

        $disabled = [
            "nick" => "disabled",
            "ip" => "disabled",
            "sid" => "disabled",
            "password" => "disabled",
            "expire" => "",
        ];

        $checked = [
            "forever" => "",
        ];

        if ($userService->getType() === ExtraFlagType::TYPE_NICK) {
            $nick = $userService->getAuthData();
            $classes["nick"] = $classes["password"] = "";
            $disabled["nick"] = $disabled["password"] = "";
        } elseif ($userService->getType() == ExtraFlagType::TYPE_IP) {
            $ip = $userService->getAuthData();
            $classes["ip"] = $classes["password"] = "";
            $disabled["ip"] = $disabled["password"] = "";
        } elseif ($userService->getType() == ExtraFlagType::TYPE_SID) {
            $sid = $userService->getAuthData();
            $classes["sid"] = "";
            $disabled["sid"] = "";
        }

        $servers = $this->getServerOptions($userService->getServerId());

        // Pobranie hasła
        if (strlen($userService->getPassword())) {
            $password = "********";
        }

        // Zamiana daty
        if ($userService->isForever()) {
            $userServiceExpire = "";
            $checked["forever"] = "checked";
            $disabled["expire"] = "disabled";
        } else {
            $userServiceExpire = as_datetime_string($userService->getExpire());
        }

        return $this->template->renderNoComments(
            "admin/services/extra_flags/user_service_admin_edit",
            compact(
                "types",
                "classes",
                "nick",
                "ip",
                "sid",
                "password",
                "services",
                "servers",
                "disabled",
                "checked",
                "userServiceExpire"
            ) + [
                "moduleId" => $this->getModuleId(),
                "userServiceId" => $userService->getId(),
                "userServiceUserId" => $userService->getUserId() ?: "",
            ]
        );
    }

    public function userServiceAdminEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $forever = (bool) array_get($body, "forever");

        $validator = new Validator(
            array_merge($body, [
                "server_id" => as_int(array_get($body, "server_id")),
                "user_id" => as_int(array_get($body, "user_id")),
            ]),
            [
                "expire" => $forever ? [] : [new RequiredRule(), new DateTimeRule()],
                "server_id" => [new RequiredRule(), new ServerExistsRule()],
                "user_id" => [new UserExistsRule()],
            ]
        );
        $this->verifyUserServiceData($validator);

        $validated = $validator->validateOrFail();
        // We need to convert date since it is accepted as a strin value.
        // DateTimeRule protects us from unparsable string
        $validated["expire"] = $forever ? null : strtotime($validated["expire"]);

        $result = $this->userServiceEdit($userService, $validated);

        if ($result) {
            $this->logger->logWithActor("log_user_service_edited", $userService->getId());
        }

        return $result;
    }

    private function verifyUserServiceData(Validator $validator)
    {
        $validator->extendData([
            "auth_data" => trim($validator->getData("auth_data")),
            "type" => as_int($validator->getData("type")),
        ]);

        $validator->extendRules([
            "auth_data" => [new RequiredRule(), new ExtraFlagAuthDataRule()],
            "password" => [],
            "type" => [
                new RequiredRule(),
                new ExtraFlagTypeRule(),
                new ExtraFlagServiceTypesRule($this->service),
            ],
        ]);
    }

    public function userServiceDeletePost(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        // Odśwież flagi gracza
        $this->playerFlagService->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );
    }

    // ----------------------------------------------------------------------------------
    // ### Edytowanie usług przez użytkownika

    public function userOwnServiceEditFormGet(UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $serviceInfo = [
            "types" => "",
            "player_nick" => "",
            "player_ip" => "",
            "player_sid" => "",
            "password" => "",
        ];
        $classes = [
            "nick" => "is-hidden",
            "ip" => "is-hidden",
            "sid" => "is-hidden",
            "password" => "is-hidden",
        ];
        $disabled = [
            "nick" => "disabled",
            "ip" => "disabled",
            "sid" => "disabled",
            "password" => "disabled",
        ];

        foreach (ExtraFlagType::ALL as $optionId) {
            // When given service doesn't support given type
            // and type of user service differs from given type
            if (!($this->service->getTypes() & $optionId) && $optionId != $userService->getType()) {
                continue;
            }

            $serviceInfo["types"] .= create_dom_element(
                "option",
                ExtraFlagType::getTypeName($optionId),
                [
                    "value" => $optionId,
                    "selected" => $optionId == $userService->getType() ? "selected" : "",
                ]
            );

            if ($optionId == $userService->getType()) {
                switch ($optionId) {
                    case ExtraFlagType::TYPE_NICK:
                        $serviceInfo["player_nick"] = $userService->getAuthData();
                        $classes["nick"] = $classes["password"] = "";
                        $disabled["nick"] = $disabled["password"] = "";
                        break;

                    case ExtraFlagType::TYPE_IP:
                        $serviceInfo["player_ip"] = $userService->getAuthData();
                        $classes["ip"] = $classes["password"] = "";
                        $disabled["ip"] = $disabled["password"] = "";
                        break;

                    case ExtraFlagType::TYPE_SID:
                        $serviceInfo["player_sid"] = $userService->getAuthData();
                        $classes["sid"] = "";
                        $disabled["sid"] = "";
                        break;
                }
            }
        }

        if (strlen($userService->getPassword()) && $userService->getPassword() != md5("")) {
            $serviceInfo["password"] = "********";
        }

        $server = $this->serverManager->getServer($userService->getServerId());
        $serviceInfo["server"] = $server->getName();
        $serviceInfo["expire"] = as_expiration_datetime_string($userService->getExpire());
        $serviceInfo["service"] = $this->service->getNameI18n();

        return $this->template->render(
            "shop/services/extra_flags/user_own_service_edit",
            compact("serviceInfo", "disabled", "classes")
        );
    }

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $server = $this->serverManager->getServer($userService->getServerId());

        return $this->template->render("shop/services/extra_flags/user_own_service", [
            "buttonEdit" => $buttonEdit,
            "authData" => $userService->getAuthData(),
            "userServiceId" => $userService->getId(),
            "expire" => as_expiration_datetime_string($userService->getExpire()),
            "moduleId" => $this->getModuleId(),
            "serverName" => $server->getName(),
            "serviceName" => $this->service->getNameI18n(),
            "type" => $this->getTypeName($userService->getType()),
        ]);
    }

    public function userOwnServiceEdit(array $body, UserService $userService)
    {
        if (!($userService instanceof ExtraFlagUserService)) {
            throw new UnexpectedValueException();
        }

        $validator = new Validator($body, [
            "password" => [
                new ExtraFlagPasswordRule(),
                new PasswordRule(),
                new ExtraFlagPasswordDiffersRule(),
            ],
        ]);
        $this->verifyUserServiceData($validator);

        $validated = $validator->validateOrFail();
        $result = $this->userServiceEdit($userService, $validated);

        if ($result) {
            $this->logger->logWithActor("log_user_edited_service", $userService->getId());
        }

        return $result;
    }

    // ----------------------------------------------------------------------------------
    // ### Dodatkowe funkcje przydatne przy zarządzaniu usługami użytkowników

    /**
     * @param ExtraFlagUserService $userService
     * @param array $data
     * @return bool
     */
    private function userServiceEdit(ExtraFlagUserService $userService, array $data)
    {
        $expire = array_key_exists("expire", $data)
            ? as_int($data["expire"])
            : $userService->getExpire();
        $type = as_int(array_get($data, "type", $userService->getType()));
        $authData = as_string(array_get($data, "auth_data", $userService->getAuthData()));
        $serverId = as_int(array_get($data, "server", $userService->getServerId()));
        $userId = as_int(array_get($data, "user_id"));
        $shouldUserBeUpdated = array_key_exists("user_id", $data);

        // Edge-case: Type is changed to SteamID from non-SteamID
        if (
            $type === ExtraFlagType::TYPE_SID &&
            $userService->getType() !== ExtraFlagType::TYPE_SID
        ) {
            $password = array_get($data, "password", "");
            $shouldPasswordBeUpdated = true;
        } else {
            $password = array_get($data, "password");
            $shouldPasswordBeUpdated = !!strlen($password);
        }

        $set = [];

        if ($shouldPasswordBeUpdated) {
            $set["password"] = $password;
        }

        if ($shouldUserBeUpdated) {
            $set["user_id"] = $userId;
        }

        if (!$expire) {
            $set["expire"] = -1;
        }

        // Sprawdzenie czy nie ma już takiej usługi
        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS usef ON us.id = usef.us_id " .
                "WHERE us.service_id = ? AND `server_id` = ? AND `type` = ? AND `auth_data` = ? AND `id` != ?"
        );
        $statement->execute([
            $this->service->getId(),
            $serverId,
            $type,
            $authData,
            $userService->getId(),
        ]);
        $existingUserServiceData = $statement->fetch();

        if ($existingUserServiceData) {
            $existingUserService = $this->mapToUserService($existingUserServiceData);
            // Since $shouldUidBeUpdated is false we can assume that it is action done via ACP
            // not by "user own service edit"
            $canManageThisUserService =
                !$shouldUserBeUpdated &&
                $userService->getUserId() != $existingUserService->getUserId();

            if ($canManageThisUserService) {
                throw new ValidationException([
                    "auth_data" => [$this->lang->t("service_isnt_yours")],
                ]);
            }

            $this->userServiceRepository->delete($userService->getId());

            // Dodajemy expire
            if ($expire) {
                $set["expire"] = new Expression("( `expire` - UNIX_TIMESTAMP() + $expire )");
            }

            // Aktualizujemy usługę, która już istnieje w bazie i ma takie same dane jak nasze nowe
            $affected = $this->userServiceRepository->updateWithModule(
                $this,
                $existingUserService->getId(),
                $set
            );
        } else {
            $set["service_id"] = $this->service->getId();
            $set["server_id"] = $serverId;
            $set["type"] = $type;
            $set["auth_data"] = $authData;

            if ($expire) {
                $set["expire"] = $expire;
            }

            $affected = $this->userServiceRepository->updateWithModule(
                $this,
                $userService->getId(),
                $set
            );
        }

        // Ustaw jednakowe hasła, żeby potem nie było problemów z różnymi hasłami
        if ($shouldPasswordBeUpdated) {
            $this->db
                ->statement(
                    "UPDATE `{$this->getUserServiceTable()}` " .
                        "SET `password` = ? " .
                        "WHERE `server_id` = ? AND `type` = ? AND `auth_data` = ?"
                )
                ->execute([$password, $serverId, $type, $authData]);
        }

        // Przelicz flagi tylko wtedy, gdy coś się zmieniło
        if (!$affected) {
            return false;
        }

        // Odśwież flagi gracza ( przed zmiana danych )
        $this->playerFlagService->recalculatePlayerFlags(
            $userService->getServerId(),
            $userService->getType(),
            $userService->getAuthData()
        );

        // Odśwież flagi gracza ( już po edycji )
        $this->playerFlagService->recalculatePlayerFlags($serverId, $type, $authData);

        return true;
    }

    public function serviceTakeOverFormGet()
    {
        $types = $this->getTypeOptions($this->service->getTypes());
        $servers = $this->getServerOptions();

        return $this->template->render(
            "shop/services/extra_flags/service_take_over",
            compact("servers", "types") + ["moduleId" => $this->getModuleId()]
        );
    }

    public function serviceTakeOver(array $body)
    {
        $paymentMethodId = array_get($body, "payment_method");

        try {
            $paymentMethod = $this->serviceTakeOverFactory->create($paymentMethodId);
        } catch (InvalidArgumentException $e) {
            throw new ValidationException([
                "payment_method" => "Invalid value",
            ]);
        }

        $validator = new Validator(
            array_merge($body, [
                "auth_data" => trim(array_get($body, "auth_data")),
                "password" => array_get($body, "password") ?: "",
                "server_id" => as_int(array_get($body, "server_id")),
                "type" => as_int(array_get($body, "type")),
            ]),
            [
                "auth_data" => [new RequiredRule(), new ExtraFlagAuthDataRule()],
                "password" => [new ExtraFlagPasswordRule()],
                "payment_id" => [new RequiredRule()],
                "server_id" => [new RequiredRule()],
                "type" => [new RequiredRule(), new ExtraFlagTypeRule()],
            ]
        );

        $validated = $validator->validateOrFail();
        $paymentId = $validated["payment_id"];
        $serverId = $validated["server_id"];
        $authData = $validated["auth_data"];
        $type = $validated["type"];
        $password = $validated["password"];

        if (!$paymentMethod->isValid($paymentId, $this->service->getId(), $authData, $serverId)) {
            return [
                "status" => "no_service",
                "text" => $this->lang->t("no_user_service"),
                "positive" => false,
            ];
        }

        $user = $this->auth->user();

        $statement = $this->db->statement(
            "SELECT `id` FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS usef ON us.id = usef.us_id " .
                "WHERE us.service_id = ? AND `server_id` = ? AND `type` = ? AND `auth_data` = ? AND `password` = ?"
        );
        $statement->execute([$this->service->getId(), $serverId, $type, $authData, $password]);

        if (!$statement->rowCount()) {
            return [
                "status" => "no_service",
                "text" => $this->lang->t("no_user_service"),
                "positive" => false,
            ];
        }

        $row = $statement->fetch();
        $this->userServiceRepository->updateUserId($row["id"], $user->getId());

        return [
            "status" => "ok",
            "text" => $this->lang->t("service_taken_over"),
            "positive" => true,
        ];
    }

    /**
     * Get available servers for given service
     *
     * @param int|null $selectedServerId
     * @return string
     */
    private function getServerOptions($selectedServerId = null)
    {
        return collect($this->serverManager->getServers())
            ->filter(function (Server $server) {
                return $this->serverServiceManager->serverServiceLinked(
                    $server->getId(),
                    $this->service->getId()
                );
            })
            ->map(function (Server $server) use ($selectedServerId) {
                return create_dom_element("option", $server->getName(), [
                    "value" => $server->getId(),
                    "selected" => $selectedServerId === $server->getId() ? "selected" : "",
                ]);
            })
            ->join();
    }

    /**
     * @param int $availableTypes
     * @param int $selectedTypes
     * @return string
     */
    private function getTypeOptions($availableTypes, $selectedTypes = 0)
    {
        return collect(ExtraFlagType::ALL)
            ->filter(function ($optionId) use ($availableTypes) {
                return $availableTypes & $optionId;
            })
            ->map(function ($optionId) use ($selectedTypes) {
                return create_dom_element("option", ExtraFlagType::getTypeName($optionId), [
                    "value" => $optionId,
                    "selected" => $optionId & $selectedTypes ? "selected" : "",
                ]);
            })
            ->join();
    }

    /**
     * Get available prices for given server
     *
     * @param int $serverId
     * @return string
     */
    private function pricesForServer($serverId)
    {
        $server = $this->serverManager->getServer($serverId);
        $service = $this->service;

        $quantities = collect($this->purchasePriceService->getServicePrices($service, $server))
            ->map(function (QuantityPrice $price) {
                return $this->purchasePriceRenderer->render($price, $this->service);
            })
            ->join();

        return $this->template->render(
            "shop/services/extra_flags/prices_for_server",
            compact("quantities")
        );
    }

    public function actionExecute($action, array $body)
    {
        switch ($action) {
            case "prices_for_server":
                return $this->pricesForServer((int) $body["server_id"]);
            case "servers_for_service":
                return $this->getServerOptions(as_int($body["server_id"]));
            default:
                return "";
        }
    }

    private function getTypeName($value)
    {
        if ($value == ExtraFlagType::TYPE_NICK) {
            return $this->lang->t("nick");
        }

        if ($value == ExtraFlagType::TYPE_IP) {
            return $this->lang->t("ip");
        }

        if ($value == ExtraFlagType::TYPE_SID) {
            return $this->lang->t("sid");
        }

        return "";
    }
}
