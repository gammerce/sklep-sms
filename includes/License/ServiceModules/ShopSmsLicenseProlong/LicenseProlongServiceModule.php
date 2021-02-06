<?php
namespace App\License\ServiceModules\ShopSmsLicenseProlong;

use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\License\Models\LicenseUserService;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PaymentMethod;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\ServiceModule;
use App\License\ServiceModules\ShopSmsLicense\LicenseUserServiceRepository;
use App\License\ServiceModules\ShopSmsLicense\Rules\LicenseProlongableRule;
use App\License\LicenseServerService;
use App\Support\PriceTextService;
use App\Service\ServiceDescriptionService;
use App\Support\Database;
use App\Support\Template;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\View\Html\DOMElement;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class LicenseProlongServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceActionExecute,
    IServiceUserServiceAdminAdd,
    IServiceCreate,
    IServicePromoCode
{
    const MODULE_ID = "shopsms_license_prolong";
    const USER_SERVICE_TABLE = "ss_user_service_shopsms_license";

    private Translator $lang;
    private Auth $auth;
    private LicenseServerService $licenseServerService;
    private BoughtServiceService $boughtServiceService;
    private AdminPaymentService $adminPaymentService;
    private DatabaseLogger $logger;
    private LicenseUserServiceRepository $licenseUserServiceRepository;
    private PriceTextService $priceTextService;
    private Database $db;

    public function __construct(
        AdminPaymentService $adminPaymentService,
        Auth $auth,
        BoughtServiceService $boughtServiceService,
        Database $db,
        DatabaseLogger $logger,
        LicenseServerService $licenseServerService,
        LicenseUserServiceRepository $licenseUserServiceRepository,
        PriceTextService $priceTextService,
        ServiceDescriptionService $serviceDescriptionService,
        Template $template,
        TranslationManager $translationManager,
        Service $service = null
    ) {
        parent::__construct($template, $serviceDescriptionService, $service);
        $this->adminPaymentService = $adminPaymentService;
        $this->auth = $auth;
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->licenseServerService = $licenseServerService;
        $this->licenseUserServiceRepository = $licenseUserServiceRepository;
        $this->logger = $logger;
        $this->priceTextService = $priceTextService;
        $this->lang = $translationManager->user();
    }

    public function mapToUserService(array $data): LicenseUserService
    {
        return $this->licenseUserServiceRepository->mapToModel($data);
    }

    public function purchaseFormGet(array $query): string
    {
        return $this->template->render("shop/services/shopsms_license_prolong/purchase_form", [
            "identifier" => array_get($query, "identifier", ""),
            "serviceId" => $this->service->getId(),
            "serviceTag" => $this->service->getTag(),
            "user" => $this->auth->user(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body): void
    {
        $validator = new Validator($body, [
            "amount" => [new RequiredRule(), new IntegerRule(), new MinValueRule(30)],
            "identifier" => [new RequiredRule(), new LicenseProlongableRule()],
        ]);
        $validated = $validator->validateOrFail();

        $amount = $validated["amount"];
        $identifier = $validated["identifier"];
        $transferPrice = intval($this->getCost($identifier, $amount) * $amount);

        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $amount,
            "identifier" => $identifier,
        ]);
        $purchase->setPayment([
            Purchase::PAYMENT_PRICE_TRANSFER => $transferPrice,
        ]);
        $purchase->getPaymentSelect()->disallowPaymentMethod(PaymentMethod::SMS());
    }

    public function orderDetails(Purchase $purchase): string
    {
        return $this->template->renderNoComments(
            "shop/services/shopsms_license_prolong/order_details",
            compact("identifier") + [
                "identifier" => $purchase->getOrder("identifier"),
                "quantity" => $purchase->getOrder(Purchase::ORDER_QUANTITY),
                "serviceName" => $this->service->getName(),
                "serviceTag" => $this->service->getTag(),
            ]
        );
    }

    public function purchase(Purchase $purchase): int
    {
        $statement = $this->db->statement(
            "SELECT * FROM `ss_user_service` AS us " .
                "INNER JOIN `{$this->getUserServiceTable()}` AS m ON m.us_id = us.id " .
                "WHERE m.identifier = ?"
        );
        $statement->execute([$purchase->getOrder("identifier")]);

        $data = $statement->fetch();
        $userService = $this->mapToUserService($data);

        $lifetime = $purchase->getOrder(Purchase::ORDER_QUANTITY) * 24 * 60 * 60;
        $result = $this->licenseServerService->prolong(
            $userService->getExternalLicenseId(),
            $lifetime
        );
        $expiresAt = $result["expires_at"];
        $promoCode = $purchase->getPromoCode();

        // Update license expire time
        $this->db
            ->statement("UPDATE `ss_user_service` SET `expire` = ? WHERE `id` = ?")
            ->execute([$expiresAt, $userService->getId()]);

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $userService->getIdentifier(),
            $userService->getEmail(),
            $promoCode ? $promoCode->getCode() : null,
            [
                "expire" => as_datetime_string($expiresAt),
            ]
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $identifier = $transaction->getAuthData();

        if ($action === "email") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license_prolong/purchase_info_email",
                [
                    "expire" => $transaction->getExtraDatum("expire"),
                    "identifier" => $identifier,
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license_prolong/purchase_info_web",
                [
                    "expire" => $transaction->getExtraDatum("expire"),
                    "identifier" => $identifier,
                    "serviceName" => $this->service->getName(),
                ]
            );
        }

        if ($action === "payment_log") {
            return [
                "text" => $this->lang->t(
                    "license_prolonged",
                    $identifier,
                    $transaction->getQuantity()
                ),
                "class" => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function userServiceAdminAddFormGet(): string
    {
        return $this->template->renderNoComments(
            "admin/services/shopsms_license_prolong/user_service_admin_add",
            [
                "moduleId" => $this->getModuleId(),
            ]
        );
    }

    public function userServiceAdminAdd(Request $request): void
    {
        $validator = new Validator($request->request->all(), [
            "amount" => [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
            "comment" => [],
            "identifier" => [new RequiredRule(), new LicenseProlongableRule()],
        ]);

        $validated = $validator->validateOrFail();

        $admin = $this->auth->user();
        $paymentId = $this->adminPaymentService->payByAdmin(
            $admin,
            get_ip($request),
            get_platform($request)
        );

        $purchase = (new Purchase($admin, get_ip($request), get_platform($request)))
            ->setServiceId($this->service->getId())
            ->setPayment([
                "method" => "admin",
                "payment_id" => $paymentId,
            ])
            ->setOrder([
                "identifier" => $validated["identifier"],
                Purchase::ORDER_QUANTITY => $validated["amount"],
            ])
            ->setComment($validated["comment"]);

        $boughtServiceId = $this->purchase($purchase);
        $this->logger->logWithActor("log_user_service_added", $boughtServiceId);
    }

    public function actionExecute($action, array $body): string
    {
        if ($action === "get_cost") {
            $daysAmount = array_get($body, "amount");
            $identifier = array_get($body, "identifier");

            $cost = $this->getCost($identifier, $daysAmount) * $daysAmount;
            $bargainPercentage = $this->getBargainPercentage($daysAmount);

            $output = $this->priceTextService->getPlainPrice($cost);
            if ($bargainPercentage) {
                $output .= "&nbsp;";
                $output .= (new DOMElement("sup", "-{$bargainPercentage}%"))->addClass("discount");
            }

            return $output;
        }

        throw new UnexpectedValueException();
    }

    /**
     * Calculates daily cost
     *
     * @param $identifier
     * @param $amount
     * @return int|null
     */
    private function getCost($identifier, $amount): ?int
    {
        if (!my_is_integer($amount) || $amount < 30) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT `cost_daily` FROM `{$this->getUserServiceTable()}` WHERE `identifier` = ?"
        );
        $statement->execute([$identifier]);
        $costDaily = $statement->fetchColumn();

        if ($costDaily === null) {
            return null;
        }

        return (int) ceil($costDaily * $this->getBargain($amount));
    }

    private function getBargainPercentage($daysCount): int
    {
        if ($daysCount >= 365) {
            return 20;
        }

        return 0;
    }

    private function getBargain($daysCount): float
    {
        return (100 - $this->getBargainPercentage($daysCount)) / 100;
    }

    public function showOnWeb(): bool
    {
        return true;
    }
}
