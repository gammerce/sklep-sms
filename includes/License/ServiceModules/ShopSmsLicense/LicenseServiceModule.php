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
use App\License\Models\LicenseUserService;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\UserService;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PurchaseDataService;
use App\Repositories\UserServiceRepository;
use App\Server\Platform;
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
use App\License\LicenseServerService;
use App\Support\PriceTextService;
use App\Service\ServiceDescriptionService;
use App\Service\UserServiceService;
use App\Support\Database;
use App\Support\QueryParticle;
use App\Support\Template;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\User\Permission;
use App\View\Html\BodyRow;
use App\View\Html\Cell;
use App\View\Html\DOMElement;
use App\View\Html\ExpirationCell;
use App\View\Html\HeadCell;
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

    private Translator $lang;
    private Auth $auth;
    private BoughtServiceService $boughtServiceService;
    private LicensePriceService $licensePriceService;
    private LicenseServerService $licenseServerService;
    private UserServiceService $userServiceService;
    private UserServiceRepository $userServiceRepository;
    private PurchaseDataService $purchaseDataService;
    private LicenseUserServiceRepository $licenseUserServiceRepository;
    private PriceTextService $priceTextService;
    private PlatformService $platformService;
    private Settings $settings;
    private Database $db;
    private PaginationFactory $paginationFactory;
    private AdminPaymentService $adminPaymentService;

    public function __construct(
        Auth $auth,
        BoughtServiceService $boughtServiceService,
        Database $db,
        LicensePriceService $licensePriceService,
        PlatformService $platformService,
        LicenseServerService $licenseServerService,
        LicenseUserServiceRepository $licenseUserServiceRepository,
        PaginationFactory $paginationFactory,
        PriceTextService $priceTextService,
        PurchaseDataService $purchaseDataService,
        ServiceDescriptionService $serviceDescriptionService,
        Settings $settings,
        Template $template,
        TranslationManager $translationManager,
        UserServiceRepository $userServiceRepository,
        UserServiceService $userServiceService,
        AdminPaymentService $adminPaymentService,
        Service $service = null
    ) {
        parent::__construct($template, $serviceDescriptionService, $service);
        $this->auth = $auth;
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->platformService = $platformService;
        $this->licensePriceService = $licensePriceService;
        $this->licenseServerService = $licenseServerService;
        $this->licenseUserServiceRepository = $licenseUserServiceRepository;
        $this->paginationFactory = $paginationFactory;
        $this->priceTextService = $priceTextService;
        $this->purchaseDataService = $purchaseDataService;
        $this->settings = $settings;
        $this->userServiceRepository = $userServiceRepository;
        $this->userServiceService = $userServiceService;
        $this->adminPaymentService = $adminPaymentService;
        $this->lang = $translationManager->user();
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
                    [
                        "us.id",
                        "us.user_id",
                        "u.username",
                        "s.name",
                        "m.external_license_id",
                        "m.identifier",
                        "m.cost_daily",
                    ],
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
                us.expire, m.identifier,
                m.external_license_id,
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
                    ->addCell(new Cell($row["external_license_id"]))
                    ->addCell(new Cell($this->priceTextService->getPriceText($row["cost_daily"])))
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
            ->addHeadCell(new HeadCell($this->lang->t("identifier")))
            ->addHeadCell(new HeadCell($this->lang->t("external_license_id")))
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
                "platforms" => [
                    new RequiredRule(),
                    new ArrayRule(),
                    new IterateRule(new EnumRule(Platform::class)),
                ],
            ]
        );

        $validated = $validator->validateOrFail();

        $costDaily = $this->licensePriceService->getDailyCost($validated["platforms"]);
        $purchase
            ->setOrder([
                Purchase::ORDER_QUANTITY => $validated["amount"],
                "platforms" => $validated["platforms"],
                "cost_daily" => $costDaily,
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
        ]);
    }

    public function purchase(Purchase $purchase): int
    {
        $platforms = $purchase->getOrder("platforms");
        $lifetime = $purchase->getOrder(Purchase::ORDER_QUANTITY) * 24 * 60 * 60;

        $result = $this->licenseServerService->create($lifetime, $platforms);
        $externalLicenseId = $result["id"];
        $token = $result["token"];
        $expiresAt = $result["expires_at"];
        $identifier = generate_uuid4();
        $promoCode = $purchase->getPromoCode();

        // Let's add a user service to the shop database
        $userServiceId = $this->userServiceRepository->createFixedExpire(
            $this->service->getId(),
            $expiresAt,
            $purchase->user->getId(),
            $purchase->getComment()
        );

        $this->db
            ->statement(
                "INSERT INTO `{$this->getUserServiceTable()}` SET " .
                    "`us_id` = ?, " .
                    "`service_id` = ?, " .
                    "`identifier` = ?, " .
                    "`external_license_id` = ?, " .
                    "`cost_daily` = ?, " .
                    "`email` = ?, " .
                    "`platform_amxmodx` = ?, " .
                    "`platform_sourcemod` = ?"
            )
            ->execute([
                $userServiceId,
                $this->service->getId(),
                $identifier,
                $externalLicenseId,
                $purchase->getOrder("cost_daily"),
                $purchase->getEmail(),
                (int) in_array(Platform::AMXMODX(), $platforms),
                (int) in_array(Platform::SOURCEMOD(), $platforms),
            ]);

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
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
            ]
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $platforms = $transaction->getExtraDatum("engines");

        if ($action === "email") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license/purchase_info_email",
                [
                    "platforms" => $platforms,
                    "expire" => $transaction->getExtraDatum("expire"),
                    "identifier" => $transaction->getExtraDatum("identifier"),
                    "token" => $transaction->getExtraDatum("token"),
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license/purchase_info_web",
                [
                    "email" => $transaction->getEmail(),
                    "platforms" => $platforms,
                    "expire" => $transaction->getExtraDatum("expire"),
                    "identifier" => $transaction->getExtraDatum("identifier"),
                    "token" => $transaction->getExtraDatum("token"),
                    "serviceName" => $this->service->getName(),
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
                "user_id" => [new UserExistsRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $admin = $this->auth->user();
        $paymentId = $this->adminPaymentService->payByAdmin(
            $admin,
            get_ip($request),
            get_platform($request)
        );

        $costDaily = $this->licensePriceService->getDailyCost($validated["platforms"]);

        $purchase = (new Purchase($admin, get_ip($request), get_platform($request)))
            ->setServiceId($this->service->getId())
            ->setPayment([
                "method" => "admin",
                "payment_id" => $paymentId,
            ])
            ->setOrder([
                Purchase::ORDER_QUANTITY => $validated["quantity"],
                "platforms" => $validated["platforms"],
                "cost_daily" => $costDaily,
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
            "platforms" => $this->platformService->formatPlatforms($platforms),
            "expire" => as_expiration_datetime_string($userService->getExpire()),
            "identifier" => $userService->getIdentifier(),
            "moduleId" => $this->getModuleId(),
            "serviceName" => $this->service->getName(),
            "userServiceId" => $userService->getId(),
        ]);
    }

    public function userOwnServiceEditFormGet(UserService $userService): string
    {
        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $platforms = [
            "amxx" => [
                "input" => $userService->hasPlatformAmxModX() ? "1" : "0",
                "div" => $userService->hasPlatformAmxModX() ? "is-active" : "",
            ],
            "sm" => [
                "input" => $userService->hasPlatformSourceMod() ? "1" : "0",
                "div" => $userService->hasPlatformSourceMod() ? "is-active" : "",
            ],
        ];

        return $this->template->render("shop/services/shopsms_license/user_own_service_edit", [
            "costMonthly" => $this->priceTextService->getPriceText(
                $userService->getCostDaily() * 30
            ),
            "email" => $userService->getEmail(),
            "platforms" => $platforms,
            "expire" => as_expiration_datetime_string($userService->getExpire()),
            "identifier" => $userService->getIdentifier(),
            "serviceId" => $this->service->getId(),
            "serviceName" => $this->service->getName(),
            "platformMonthlyPrice" => $this->priceTextService->getPriceText(
                LicensePriceService::COST_PLATFORM_PER_DAY * 30
            ),
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
        ]);

        $validated = $validator->validateOrFail();

        $costData = $this->getCostUserEdit($validated, $userService);

        $purchase = (new Purchase($this->auth->user(), get_ip($request), get_platform($request)))
            ->setServiceId("ss_license_edit")
            ->setEmail($validated["email"])
            ->setOrder([
                "user_service_id" => $validated["id"],
                "cost_daily" => $costData["cost_daily"],
                "bargain" => $costData["bargain"],
                "password" => $validated["password"],
                "platforms" => $validated["platforms"],
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
            "SELECT `us_id` FROM `{$this->getUserServiceTable()}` WHERE `service_id` = ? AND `external_license_id` = ?"
        );
        $statement->execute([$validated["service_id"], $response["id"]]);

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

        $this->licenseServerService->delete($userService->getExternalLicenseId());

        return true;
    }

    public function actionExecute($action, array $body): string
    {
        if ($action === "get_cost") {
            $daysAmount = (int) $body["amount"];

            if ($daysAmount < 30) {
                return "0.00";
            }

            $dailyCost = $this->licensePriceService->getDailyCost($body["platforms"]);
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
            $userService = $this->userServiceService->findOne($body["user_service_id"]);

            if (!($userService instanceof LicenseUserService)) {
                throw new UnexpectedValueException();
            }

            $costData = $this->getCostUserEdit($body, $userService);
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

            $statement = $this->db->statement(
                "SELECT `external_license_id` FROM `{$this->getUserServiceTable()}` WHERE `identifier` = ?"
            );
            $statement->execute([$identifier]);

            if (!$statement->rowCount()) {
                return "Invalid identifier";
            }

            $row = $statement->fetch();
            $externalLicenseId = $row["external_license_id"];

            try {
                $response = $this->licenseServerService->regenerateToken($externalLicenseId);
            } catch (Exception $e) {
                return $e->getMessage();
            }

            return json_encode([
                "token" => $response["token"],
            ]);
        }

        return "no_action";
    }

    private function getCostUserEdit(array $body, LicenseUserService $userService): array
    {
        $daysLeft = ceil(($userService->getExpire() - time()) / (24 * 60 * 60));

        $platforms = [
            [
                "old" => $userService->hasPlatformAmxModX(),
                "new" => in_array(Platform::AMXMODX(), $body["platforms"]),
            ],
            [
                "old" => $userService->hasPlatformSourceMod(),
                "new" => in_array(Platform::SOURCEMOD(), $body["platforms"]),
            ],
        ];

        // TODO Replace all platform_amxmodx
        // TODO Replace all platform_sourcemod

        $additionalCost = 0;
        foreach ($platforms as $platformData) {
            // If we cancel support for a platform, we lose all discounts
            if ($platformData["old"] && !$platformData["new"]) {
                $costDaily = $this->licensePriceService->getDailyCost($body["platforms"]);
                break;
            }

            // If we add support for a new platform, we add it to the daily cost
            if ($platformData["new"] && !$platformData["old"]) {
                $additionalCost += LicensePriceService::COST_PLATFORM_PER_DAY;
            }
        }

        if (!isset($costDaily)) {
            $costDaily = $userService->getCostDaily() + $additionalCost;
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
