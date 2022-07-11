<?php
namespace App\License\ServiceModules\ShopSmsLicenseProlong;

use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\License\LicensePriceService;
use App\License\LicenseServerService;
use App\License\Models\LicenseUserService;
use App\License\ServiceModules\ShopSmsLicense\LicenseUserServiceRepository;
use App\License\ServiceModules\ShopSmsLicense\Rules\LicenseProlongableRule;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\Payment\General\PaymentMethod;
use App\Payment\General\PaymentOption;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\ServiceModule;
use App\Support\Database;
use App\Support\PriceTextService;
use App\System\Auth;
use App\Theme\Template;
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
    private LicenseUserServiceRepository $licenseUserServiceRepository;
    private PriceTextService $priceTextService;
    private LicensePriceService $licensePriceService;
    private Database $db;

    public function __construct(
        AdminPaymentService $adminPaymentService,
        Auth $auth,
        BoughtServiceService $boughtServiceService,
        Database $db,
        LicensePriceService $licensePriceService,
        LicenseServerService $licenseServerService,
        LicenseUserServiceRepository $licenseUserServiceRepository,
        PriceTextService $priceTextService,
        Template $template,
        TranslationManager $translationManager,
        Service $service = null
    ) {
        parent::__construct($template, $service);
        $this->adminPaymentService = $adminPaymentService;
        $this->auth = $auth;
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->licensePriceService = $licensePriceService;
        $this->licenseServerService = $licenseServerService;
        $this->licenseUserServiceRepository = $licenseUserServiceRepository;
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
        $transferPrice = intval($this->getDailyCostForLicense($identifier, $amount) * $amount);

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
        $statement->bindAndExecute([$purchase->getOrder("identifier")]);

        $data = $statement->fetch();
        $userService = $this->mapToUserService($data);

        $lifetime = $purchase->getOrder(Purchase::ORDER_QUANTITY) * 24 * 60 * 60;
        $result = $this->licenseServerService->prolong($userService->getIdentifier(), $lifetime);
        $expiresAt = $result["expires_at"];
        $promoCode = $purchase->getPromoCode();

        // Update license expire time
        $this->db
            ->statement("UPDATE `ss_user_service` SET `expire` = ? WHERE `id` = ?")
            ->bindAndExecute([$expiresAt, $userService->getId()]);

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $purchase->getPayment(Purchase::PAYMENT_INVOICE_ID),
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

    public function userServiceAdminAdd(Request $request): int
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
            ->setService($this->service->getId(), $this->service->getName())
            ->setPaymentOption(new PaymentOption(PaymentMethod::ADMIN()))
            ->setPayment([
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ])
            ->setOrder([
                "identifier" => $validated["identifier"],
                Purchase::ORDER_QUANTITY => $validated["amount"],
            ])
            ->setComment($validated["comment"]);

        return $this->purchase($purchase);
    }

    public function actionExecute($action, array $body): string
    {
        if ($action === "get_cost") {
            $daysAmount = array_get($body, "amount");
            $identifier = array_get($body, "identifier");

            $cost = $this->getDailyCostForLicense($identifier, $daysAmount) * $daysAmount;
            $bargainPercentage = $this->licensePriceService->getBargainPercentage($daysAmount);

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
    public function getDailyCostForLicense($identifier, $amount): ?int
    {
        if (!my_is_integer($amount) || $amount < 30) {
            return null;
        }

        $statement = $this->db->statement(
            "SELECT `cost_daily` FROM `{$this->getUserServiceTable()}` WHERE `identifier` = ?"
        );
        $statement->bindAndExecute([$identifier]);
        $costDaily = $statement->fetchColumn();

        if ($costDaily === null) {
            return null;
        }

        return (int) ceil($costDaily * $this->licensePriceService->getBargain($amount));
    }

    public function showOnWeb(): bool
    {
        return true;
    }
}
