<?php
namespace App\ServiceModules\ShopSmsLicenseProlong;

use App\Http\Validation\Rules\IntegerRule;
use App\Http\Validation\Rules\MinValueRule;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Models\LicenseUserService;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\BoughtServiceService;
use App\ServiceModules\Interfaces\IServiceActionExecute;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\Interfaces\IServiceUserServiceAdminAdd;
use App\ServiceModules\ServiceModule;
use App\ServiceModules\ShopSmsLicense\LicenseUserServiceRepository;
use App\ServiceModules\ShopSmsLicense\Rules\LicenseProlongableRule;
use App\Services\LicenseServerService;
use App\Services\PriceTextService;
use App\System\Auth;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class LicenseProlongServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceActionExecute,
    IServiceUserServiceAdminAdd
{
    const MODULE_ID = "shopsms_license_prolong";
    const USER_SERVICE_TABLE = "ss_user_service_shopsms_license";

    /** @var Translator */
    private $lang;

    /** @var Auth */
    private $auth;

    /** @var LicenseServerService */
    private $licenseServerService;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var AdminPaymentService */
    private $adminPaymentService;

    /** @var DatabaseLogger */
    private $logger;

    /** @var LicenseUserServiceRepository */
    private $licenseUserServiceRepository;

    /** @var PriceTextService */
    private $priceTextService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->auth = $this->app->make(Auth::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->adminPaymentService = $this->app->make(AdminPaymentService::class);
        $this->logger = $this->app->make(DatabaseLogger::class);
        $this->licenseUserServiceRepository = $this->app->make(LicenseUserServiceRepository::class);
        $this->priceTextService = $this->app->make(PriceTextService::class);
    }

    /**
     * @param array $data
     * @return LicenseUserService
     */
    public function mapToUserService(array $data)
    {
        return $this->licenseUserServiceRepository->mapToModel($data);
    }

    public function purchaseFormGet(array $query)
    {
        /** @var Request $request */
        $request = $this->app->make(Request::class);

        return $this->template->render("shop/services/shopsms_license_prolong/purchase_form", [
            "identifier" => $request->query->get("identifier", ""),
            "serviceId" => $this->service->getId(),
            "serviceTag" => $this->service->getTag(),
            "user" => $this->auth->user(),
        ]);
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
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
            Purchase::PAYMENT_DISABLED_SMS => true,
        ]);
    }

    public function orderDetails(Purchase $purchase)
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

    public function purchase(Purchase $purchase)
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

        // Update license expire time
        $this->db
            ->statement("UPDATE `ss_user_service` SET `expire` = ? WHERE `id` = ?")
            ->execute([$expiresAt, $userService->getId()]);

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $userService->getIdentifier(),
            $userService->getEmail(),
            [
                "expire" => convert_date($expiresAt),
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

    public function userServiceAdminAddFormGet()
    {
        return $this->template->renderNoComments(
            "admin/services/shopsms_license_prolong/user_service_admin_add",
            [
                "moduleId" => $this->getModuleId(),
            ]
        );
    }

    public function userServiceAdminAdd(array $body)
    {
        $validator = new Validator($body, [
            "amount" => [new RequiredRule(), new NumberRule(), new MinValueRule(0)],
            "identifier" => [new RequiredRule(), new LicenseProlongableRule()],
        ]);

        $validated = $validator->validateOrFail();

        $admin = $this->auth->user();
        $paymentId = $this->adminPaymentService->payByAdmin($admin);

        $purchase = new Purchase($admin);
        $purchase->setServiceId($this->service->getId());
        $purchase->setPayment([
            "method" => "admin",
            "payment_id" => $paymentId,
        ]);
        $purchase->setOrder([
            "identifier" => $validated["identifier"],
            Purchase::ORDER_QUANTITY => $validated["amount"],
        ]);

        $boughtServiceId = $this->purchase($purchase);
        $this->logger->logWithActor("log_user_service_added", $boughtServiceId);

        return [
            "status" => "ok",
            "text" => $this->lang->t("service_added_correctly"),
            "positive" => true,
        ];
    }

    public function actionExecute($action, array $body)
    {
        if ($action === "get_cost") {
            $amount = array_get($body, "amount");
            $identifier = array_get($body, "identifier");
            $cost = $this->getCost($identifier, $amount) * $amount;

            return $this->priceTextService->getPriceText($cost) ?: $this->lang->t("none");
        }

        throw new UnexpectedValueException();
    }

    /**
     * Calculates daily cost
     *
     * @param $identifier
     * @param $amount
     * @return int
     */
    private function getCost($identifier, $amount)
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

    private function getBargain($daysAmount)
    {
        if ($daysAmount >= 365) {
            return 0.8;
        }

        return 1.0;
    }

    public function showOnWeb()
    {
        return true;
    }
}
