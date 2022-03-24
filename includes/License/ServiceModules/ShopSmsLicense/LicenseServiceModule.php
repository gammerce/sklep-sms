<?php
namespace App\License\ServiceModules\ShopSmsLicense;

use App\Http\Validation\Rules\ArrayRule;
use App\Http\Validation\Rules\EmailRule;
use App\Http\Validation\Rules\EnumRule;
use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\IterateRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\PasswordRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Validator;
use App\License\LicensePriceService;
use App\License\LicenseServerService;
use App\License\Models\LicenseUserService;
use App\License\ProxyManagerClient;
use App\License\SubdomainRule;
use App\License\SubdomainUtil;
use App\Managers\ServiceManager;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserService;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\Payment\General\PurchaseDataService;
use App\Server\Platform;
use App\Service\UserServiceService;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceTakeOver;
use App\ServiceModules\Interfaces\IServiceUserOwnServices;
use App\ServiceModules\Interfaces\IServiceUserOwnServicesEdit;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminDisplay;
use App\ServiceModules\ServiceModule;
use App\Support\Collection;
use App\Support\Database;
use App\Support\PriceTextService;
use App\Support\QueryParticle;
use App\System\Auth;
use App\System\Settings;
use App\Theme\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DOMElement;
use App\View\Html\ExpirationCell;
use App\View\Html\HeadCell;
use App\View\Html\NoneText;
use App\View\Html\PreWrapCell;
use App\View\Html\ServiceRef;
use App\View\Html\Structure;
use App\View\Html\UserRef;
use App\View\Html\Wrapper;
use App\View\Pagination\PaginationFactory;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class LicenseServiceModule extends ServiceModule implements
    IServiceUserServiceAdminDisplay,
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceActionExecute,
    IServiceTakeOver,
    IServiceUserOwnServices,
    IServiceUserOwnServicesEdit,
    IServiceUserServiceAdminAdd,
    IServiceCreate,
    IServicePromoCode
{
    const MODULE_ID = "shopsms_license";
    const USER_SERVICE_TABLE = "ss_user_service_shopsms_license";

    private AdminPaymentService $adminPaymentService;
    private Auth $auth;
    private BoughtServiceService $boughtServiceService;
    private Database $db;
    private LicensePriceService $licensePriceService;
    private LicenseServerService $licenseServerService;
    private LicenseUserServiceRepository $licenseUserServiceRepository;
    private PaginationFactory $paginationFactory;
    private PlatformService $platformService;
    private PriceTextService $priceTextService;
    private ProxyManagerClient $proxyManagerClient;
    private PurchaseDataService $purchaseDataService;
    private ServiceManager $serviceManager;
    private Settings $settings;
    private Translator $lang;
    private UserServiceService $userServiceService;

    public function __construct(
        AdminPaymentService $adminPaymentService,
        Auth $auth,
        BoughtServiceService $boughtServiceService,
        Database $db,
        LicensePriceService $licensePriceService,
        LicenseServerService $licenseServerService,
        LicenseUserServiceRepository $licenseUserServiceRepository,
        PaginationFactory $paginationFactory,
        PlatformService $platformService,
        PriceTextService $priceTextService,
        ProxyManagerClient $proxyManagerClient,
        PurchaseDataService $purchaseDataService,
        ServiceManager $serviceManager,
        Settings $settings,
        Template $template,
        TranslationManager $translationManager,
        UserServiceService $userServiceService,
        Service $service = null
    ) {
        parent::__construct($template, $service);
        $this->adminPaymentService = $adminPaymentService;
        $this->auth = $auth;
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->lang = $translationManager->user();
        $this->licensePriceService = $licensePriceService;
        $this->licenseServerService = $licenseServerService;
        $this->licenseUserServiceRepository = $licenseUserServiceRepository;
        $this->paginationFactory = $paginationFactory;
        $this->platformService = $platformService;
        $this->priceTextService = $priceTextService;
        $this->proxyManagerClient = $proxyManagerClient;
        $this->purchaseDataService = $purchaseDataService;
        $this->serviceManager = $serviceManager;
        $this->settings = $settings;
        $this->userServiceService = $userServiceService;
    }

    public function mapToUserService(array $data): LicenseUserService
    {
        return $this->licenseUserServiceRepository->mapToModel($data);
    }

    public function userServiceAdminDisplayTitleGet(): string
    {
        return $this->lang->t("licenses");
    }

    public function userServiceAdminDisplayGet(Request $request): Wrapper
    {
        $pagination = $this->paginationFactory->create($request);
        $queryParticle = new QueryParticle();

        if ($request->query->has("search")) {
            $queryParticle->extend(
                create_search_query(
                    ["us.id", "us.user_id", "u.username", "s.name", "m.identifier", "m.cost_daily"],
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
    m.identifier,
    m.subdomain,
    m.cost_daily
FROM `ss_user_service` AS us
INNER JOIN `{$this->getUserServiceTable()}` AS m ON m.us_id = us.id
LEFT JOIN `ss_services` AS s ON s.id = m.service_id
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
                    : $this->lang->t("none");

                return (new BodyRow())
                    ->setDbId($row["id"])
                    ->addCell(new Cell($userEntry))
                    ->addCell(new Cell(new ServiceRef($row["service_id"], $row["service"])))
                    ->addCell(new Cell($row["identifier"]))
                    ->addCell(new Cell($row["subdomain"] ?: new NoneText()))
                    ->addCell(new Cell($this->priceTextService->getPriceText($row["cost_daily"])))
                    ->addCell(new ExpirationCell($row["expire"]))
                    ->addCell(new PreWrapCell($row["comment"]))
                    ->setDeleteAction(can(Permission::USER_SERVICES_MANAGEMENT()))
                    ->setEditAction(false);
            })
            ->all();

        $table = (new Structure())
            ->addHeadCell(new HeadCell($this->lang->t("id"), "id"))
            ->addHeadCell(new HeadCell($this->lang->t("user")))
            ->addHeadCell(new HeadCell($this->lang->t("service")))
            ->addHeadCell(new HeadCell($this->lang->t("identifier")))
            ->addHeadCell(new HeadCell($this->lang->t("subdomain")))
            ->addHeadCell(new HeadCell($this->lang->t("cost_daily")))
            ->addHeadCell(new HeadCell($this->lang->t("expires")))
            ->addHeadCell(new HeadCell($this->lang->t("comment")))
            ->addBodyRows($bodyRows)
            ->enablePagination("/admin/user_service", $pagination, $rowsCount);

        return (new Wrapper())->enableSearch()->setTable($table);
    }

    public function purchaseFormGet(array $query): string
    {
        return $this->template->render("shop/services/shopsms_license/purchase_form", [
            "user" => $this->auth->user(),
            "serviceId" => $this->service->getId(),
            "serviceTag" => $this->service->getTag(),
            "days" => array_get($query, "days"),
            "platformMonthlyPrice" => $this->priceTextService->getPriceText(
                LicensePriceService::COST_PLATFORM_PER_DAY * 30
            ),
            "hostingMonthlyPrice" => $this->priceTextService->getPriceText(
                LicensePriceService::COST_HOSTING_PER_DAY * 30
            ),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body): void
    {
        $validator = new Validator(
            array_merge($body, [
                "email" => trim(array_get($body, "email")),
            ]),
            [
                "amount" => [new RequiredRule(), new IntegerRule(), new MinValueRule(30)],
                "email" => [new RequiredRule(), new EmailRule()],
                "subdomain" => [new SubdomainRule()],
                "platforms" => [
                    new RequiredRule(),
                    new ArrayRule(),
                    new IterateRule(new EnumRule(Platform::class)),
                ],
            ]
        );

        $validated = $validator->validateOrFail();
        $subdomain = $validated["subdomain"] ?: null;

        $costDaily = $this->licensePriceService->getDailyCost($validated["platforms"], $subdomain);
        $purchase
            ->setOrder([
                Purchase::ORDER_QUANTITY => $validated["amount"],
                "platforms" => $validated["platforms"],
                "cost_daily" => $costDaily,
                SubdomainUtil::PURCHASE_KEY => $subdomain,
            ])
            ->setEmail($validated["email"])
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => $this->licensePriceService->getCost(
                    $costDaily,
                    $validated["amount"]
                ),
            ])
            ->getPaymentSelect()
            ->disallowPaymentMethod(PaymentMethod::SMS());
    }

    public function orderDetails(Purchase $purchase): string
    {
        $subdomain = $purchase->getOrder(SubdomainUtil::PURCHASE_KEY);

        return $this->template->renderNoComments("shop/services/shopsms_license/order_details", [
            "costMonthly" => $this->priceTextService->getPriceText(
                $purchase->getOrder("cost_daily") * 30
            ),
            "email" => $purchase->getEmail() ?: $this->lang->t("none"),
            "platforms" => $this->platformService->formatPlatforms(
                $purchase->getOrder("platforms")
            ),
            "quantity" => $purchase->getOrder(Purchase::ORDER_QUANTITY),
            "serviceName" => $this->service->getName(),
            "serviceTag" => $this->service->getTag(),
            "subdomain" => SubdomainUtil::getText($subdomain),
        ]);
    }

    public function purchase(Purchase $purchase): int
    {
        $platforms = $purchase->getOrder("platforms");
        $subdomain = $purchase->getOrder(SubdomainUtil::PURCHASE_KEY);
        $lifetime = $purchase->getOrder(Purchase::ORDER_QUANTITY) * 24 * 60 * 60;

        $identifier = generate_uuid4();
        $result = $this->licenseServerService->create($identifier, $lifetime, $platforms);
        $token = $result["token"];
        $expiresAt = $result["expires_at"];
        $promoCode = $purchase->getPromoCode();

        // Let's add a user service to the shop database
        $this->licenseUserServiceRepository->create(
            $this->service->getId(),
            $expiresAt,
            $purchase->user->getId(),
            $purchase->getComment(),
            $identifier,
            $purchase->getOrder("cost_daily"),
            $purchase->getEmail(),
            $platforms,
            $subdomain
        );

        $boughtServiceId = $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $purchase->getPayment(Purchase::PAYMENT_INVOICE_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $identifier,
            $purchase->getEmail(),
            $promoCode ? $promoCode->getCode() : null,
            [
                "token" => $token,
                "identifier" => $identifier,
                "expire" => as_datetime_string($expiresAt),
                "engines" => $this->platformService->formatPlatforms($platforms),
                "subdomain" => $subdomain,
            ]
        );

        $this->proxyManagerClient->reload();

        return $boughtServiceId;
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $platforms = $transaction->getExtraDatum("engines");
        $subdomain = $transaction->getExtraDatum("subdomain");

        if ($action === "email") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license/purchase_info_email",
                [
                    "expire" => $transaction->getExtraDatum("expire"),
                    "identifier" => $transaction->getExtraDatum("identifier"),
                    "platforms" => $platforms,
                    "subdomain" => SubdomainUtil::getText($subdomain),
                    "token" => $transaction->getExtraDatum("token"),
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license/purchase_info_web",
                [
                    "email" => $transaction->getEmail(),
                    "expire" => $transaction->getExtraDatum("expire"),
                    "identifier" => $transaction->getExtraDatum("identifier"),
                    "platforms" => $platforms,
                    "serviceName" => $this->service->getName(),
                    "subdomain" => SubdomainUtil::getText($subdomain),
                    "token" => $transaction->getExtraDatum("token"),
                ]
            );
        }

        if ($action === "payment_log") {
            return [
                "text" => $this->lang->t("license_bought", $transaction->getQuantity()),
                "class" => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function userServiceAdminAddFormGet(): string
    {
        return $this->template->renderNoComments(
            "admin/services/shopsms_license/user_service_admin_add",
            [
                "moduleId" => $this->getModuleId(),
            ]
        );
    }

    public function userServiceAdminAdd(Request $request): int
    {
        $forever = (bool) $request->request->get("forever");

        $validator = new Validator(
            array_merge($request->request->all(), [
                "quantity" => as_int($request->request->get("quantity")),
                "server_id" => as_int($request->request->get("server_id")),
                "user_id" => as_int($request->request->get("user_id")),
            ]),
            [
                "comment" => [],
                "email" => [new EmailRule()],
                "platforms" => [
                    new RequiredRule(),
                    new ArrayRule(),
                    new IterateRule(new EnumRule(Platform::class)),
                ],
                "quantity" => $forever
                    ? []
                    : [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
                "subdomain" => [new SubdomainRule()],
                "user_id" => [new UserExistsRule()],
            ]
        );

        $validated = $validator->validateOrFail();
        $subdomain = $validated["subdomain"] ?: null;

        $admin = $this->auth->user();
        $paymentId = $this->adminPaymentService->payByAdmin(
            $admin,
            get_ip($request),
            get_platform($request)
        );

        $costDaily = $this->licensePriceService->getDailyCost($validated["platforms"], $subdomain);

        $purchase = (new Purchase($admin, get_ip($request), get_platform($request)))
            ->setService($this->service->getId(), $this->service->getName())
            ->setPaymentOption(new PaymentOption(PaymentMethod::ADMIN()))
            ->setPayment([
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ])
            ->setOrder([
                Purchase::ORDER_QUANTITY => $validated["quantity"],
                "platforms" => $validated["platforms"],
                "cost_daily" => $costDaily,
                SubdomainUtil::PURCHASE_KEY => $subdomain,
            ])
            ->setEmail($validated["email"])
            ->setComment($validated["comment"]);

        return $this->purchase($purchase);
    }

    // ------------------- My Current Services --------------------

    public function userOwnServiceInfoGet(UserService $userService, $buttonEdit): string
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $platforms = [];
        if ($userService->hasPlatformAmxModX()) {
            $platforms[] = Platform::AMXMODX();
        }
        if ($userService->hasPlatformSourceMod()) {
            $platforms[] = Platform::SOURCEMOD();
        }

        return $this->template->render("shop/services/shopsms_license/user_own_service", [
            "buttonEdit" => $buttonEdit,
            "costMonthly" => $this->priceTextService->getPriceText(
                $userService->getCostDaily() * 30
            ),
            "email" => $userService->getEmail() ?: $this->lang->t("none"),
            "expire" => as_expiration_datetime_string($userService->getExpire()),
            "identifier" => $userService->getIdentifier(),
            "moduleId" => $this->getModuleId(),
            "platforms" => $this->platformService->formatPlatforms($platforms),
            "serviceName" => $this->service->getName(),
            "subdomain" => SubdomainUtil::getLink($userService->getSubdomain()),
            "userServiceId" => $userService->getId(),
        ]);
    }

    public function userOwnServiceEditFormGet(UserService $userService): string
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $platforms = [
            Platform::AMXMODX => $userService->hasPlatformAmxModX() ? "checked" : "",
            Platform::SOURCEMOD => $userService->hasPlatformSourceMod() ? "checked" : "",
        ];

        return $this->template->render("shop/services/shopsms_license/user_own_service_edit", [
            "costMonthly" => $this->priceTextService->getPriceText(
                $userService->getCostDaily() * 30
            ),
            "email" => $userService->getEmail(),
            "expire" => as_expiration_datetime_string($userService->getExpire()),
            "hostingMonthlyPrice" => $this->priceTextService->getPriceText(
                LicensePriceService::COST_HOSTING_PER_DAY * 30
            ),
            "identifier" => $userService->getIdentifier(),
            "platformMonthlyPrice" => $this->priceTextService->getPriceText(
                LicensePriceService::COST_PLATFORM_PER_DAY * 30
            ),
            "platforms" => $platforms,
            "serviceId" => $this->service->getId(),
            "serviceName" => $this->service->getName(),
            "subdomain" => $userService->getSubdomain(),
        ]);
    }

    public function userOwnServiceEdit(Request $request, UserService $userService)
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $validator = new Validator($request->request->all(), [
            "id" => [],
            "email" => [new RequiredRule(), new EmailRule()],
            "platforms" => [
                new RequiredRule(),
                new ArrayRule(),
                new IterateRule(new EnumRule(Platform::class)),
            ],
            "password" => [new PasswordRule()],
            "subdomain" => [new SubdomainRule()],
        ]);

        $validated = $validator->validateOrFail();
        $subdomain = $validated["subdomain"] ?: null;

        $licenseEditService = $this->serviceManager->get("ss_license_edit");
        $costData = $this->getCostUserEdit($validated["platforms"], $subdomain, $userService);

        $purchase = (new Purchase($this->auth->user(), get_ip($request), get_platform($request)))
            ->setService($licenseEditService->getId(), $licenseEditService->getName())
            ->setEmail($validated["email"])
            ->setOrder([
                "user_service_id" => $validated["id"],
                "cost_daily" => $costData["cost_daily"],
                "bargain" => $costData["bargain"],
                "password" => $validated["password"],
                "platforms" => $validated["platforms"],
                SubdomainUtil::PURCHASE_KEY => $subdomain,
            ])
            ->setPayment([
                Purchase::PAYMENT_PRICE_TRANSFER => intval(
                    $costData["surcharge"] * $costData["bargain"]
                ),
            ]);

        $purchase
            ->getPaymentSelect()
            ->setTransferPaymentPlatforms($this->settings->getTransferPlatformIds())
            ->disallowPaymentMethod(PaymentMethod::SMS());

        $this->purchaseDataService->storePurchase($purchase);

        return [
            "status" => "payment",
            "text" => $this->lang->t("purchase_form_validated"),
            "positive" => true,
            "data" => [
                "transaction_id" => $purchase->getId(),
            ],
        ];
    }

    public function serviceTakeOverFormGet(): string
    {
        return $this->template->render("shop/services/shopsms_license/service_take_over");
    }

    public function serviceTakeOver(array $body): array
    {
        $validator = new Validator($body, [
            "service_id" => [new RequiredRule()],
            "token" => [new RequiredRule()],
        ]);

        $validated = $validator->validateOrFail();

        try {
            $response = $this->licenseServerService->getByToken($validated["token"]);
        } catch (Exception $e) {
            return [
                "status" => "no_service",
                "text" => $this->lang->t("no_user_service"),
                "positive" => false,
            ];
        }

        $statement = $this->db->statement(
            "SELECT `us_id` FROM `{$this->getUserServiceTable()}` WHERE `service_id` = ? AND `identifier` = ?"
        );
        $statement->execute([$validated["service_id"], $response["identifier"]]);

        $row = $statement->fetch();
        $userServiceId = $row["us_id"];

        $user = $this->auth->user();
        $statement = $this->db->statement(
            "UPDATE `ss_user_service` SET `user_id` = ? WHERE `id` = ?"
        );
        $statement->execute([$user->getId(), $userServiceId]);

        if (!$statement->rowCount()) {
            return [
                "status" => "service_not_taken_over",
                "text" => $this->lang->t("service_not_taken_over"),
                "positive" => false,
            ];
        }

        return [
            "status" => "ok",
            "text" => $this->lang->t("service_taken_over"),
            "positive" => true,
        ];
    }

    public function userServiceDelete(UserService $userService, $who): bool
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        // Do not delete if it is less than 30 days after expiration
        if ($who != "admin" && $userService->getExpire() + 60 * 60 * 24 * 30 > time()) {
            return false;
        }

        $this->licenseServerService->delete($userService->getIdentifier());

        return true;
    }

    public function actionExecute($action, array $body): string
    {
        if ($action === "get_cost") {
            $daysAmount = (int) $body["amount"];
            $platforms = to_array(array_get($body, "platforms"));
            $subdomain = array_get($body, "subdomain");

            if ($daysAmount < 30) {
                return "0.00";
            }

            $dailyCost = $this->licensePriceService->getDailyCost($platforms, $subdomain);
            $bargainPercentage = $this->licensePriceService->getBargainPercentage($daysAmount);
            $bargain = (100 - $bargainPercentage) / 100;
            $cost = (int) ceil($dailyCost * $daysAmount * $bargain);

            $output = $this->priceTextService->getPlainPrice($cost);
            if ($bargainPercentage) {
                $output .= "&nbsp;";
                $output .= (new DOMElement("sup", "-{$bargainPercentage}%"))->addClass("discount");
            }

            return $output;
        }

        if ($action === "get_cost_user_edit") {
            $platforms = array_get($body, "platforms", []);
            $subdomain = array_get($body, "subdomain");
            $userServiceId = array_get($body, "user_service_id");
            $userService = $this->userServiceService->findOne($userServiceId);

            if (!($userService instanceof LicenseUserService)) {
                throw new UnexpectedValueException();
            }

            $costData = $this->getCostUserEdit($platforms, $subdomain, $userService);
            $costData["surcharge"] = $this->priceTextService->getPlainPrice(
                $costData["surcharge"] * $costData["bargain"]
            );
            $costData["cost_monthly"] = $this->priceTextService->getPriceText(
                $costData["cost_monthly"] * $costData["bargain"]
            );
            $costData["cost_daily"] = $this->priceTextService->getPriceText(
                $costData["cost_daily"] * $costData["bargain"]
            );

            return json_encode($costData);
        }

        if ($action === "regenerate_token") {
            $identifier = array_get($body, "identifier");

            try {
                $response = $this->licenseServerService->regenerateToken($identifier);
            } catch (Exception $e) {
                return $e->getMessage();
            }

            return json_encode([
                "token" => $response["token"],
            ]);
        }

        return "no_action";
    }

    /**
     * @param Platform[] $platforms
     * @param string|null $subdomain
     * @param LicenseUserService $userService
     * @return array
     */
    private function getCostUserEdit(
        array $platforms,
        ?string $subdomain,
        LicenseUserService $userService
    ): array {
        $daysLeft = ceil(($userService->getExpire() - time()) / (24 * 60 * 60));

        $platformsData = [
            [
                "old" => $userService->hasPlatformAmxModX(),
                "new" => in_array(Platform::AMXMODX(), $platforms),
            ],
            [
                "old" => $userService->hasPlatformSourceMod(),
                "new" => in_array(Platform::SOURCEMOD(), $platforms),
            ],
        ];

        // If we cancel support for a platform, we lose all discounts
        $loseDiscounts = (new Collection($platformsData))->some(
            fn(array $platformData) => $platformData["old"] && !$platformData["new"]
        );

        if ($loseDiscounts) {
            $costDaily = $this->licensePriceService->getDailyCost($platforms, $subdomain);
        } else {
            // If we add support for a new platform, we add it to the daily cost
            $newPlatformsCount = (new Collection($platformsData))
                ->filter(fn(array $platformData) => $platformData["new"] && !$platformData["old"])
                ->count();

            $subdomainCost =
                strlen($subdomain) && !$userService->getSubdomain()
                    ? LicensePriceService::COST_HOSTING_PER_DAY
                    : 0;

            $costDaily =
                $userService->getCostDaily() +
                $newPlatformsCount * LicensePriceService::COST_PLATFORM_PER_DAY +
                $subdomainCost;
        }

        return [
            "surcharge" => max(0, ($costDaily - $userService->getCostDaily()) * $daysLeft),
            "cost_daily" => $costDaily,
            "cost_monthly" => $costDaily * 30,
            "bargain" => $this->licensePriceService->getBargain($daysLeft),
        ];
    }

    public function showOnWeb(): bool
    {
        return true;
    }
}
