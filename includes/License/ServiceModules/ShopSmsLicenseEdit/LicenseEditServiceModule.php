<?php
namespace App\License\ServiceModules\ShopSmsLicenseEdit;

use App\License\Models\LicenseUserService;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\General\BoughtServiceService;
use App\Repositories\UserServiceRepository;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\License\ServiceModules\ShopSmsLicense\EngineService;
use App\License\LicenseServerService;
use App\Support\PriceTextService;
use App\Service\ServiceDescriptionService;
use App\Service\UserServiceService;
use App\Support\Database;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use UnexpectedValueException;

class LicenseEditServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseWeb,
    IServiceCreate,
    IServicePromoCode
{
    const MODULE_ID = "shopsms_license_edit";
    const USER_SERVICE_TABLE = "ss_user_service_shopsms_license";

    private Translator $lang;
    private LicenseServerService $licenseServerService;
    private BoughtServiceService $boughtServiceService;
    private UserServiceService $userServiceService;
    private PriceTextService $priceTextService;
    private EngineService $engineService;
    private UserServiceRepository $userServiceRepository;
    private Database $db;

    public function __construct(
        BoughtServiceService $boughtServiceService,
        Database $db,
        EngineService $engineService,
        LicenseServerService $licenseServerService,
        PriceTextService $priceTextService,
        ServiceDescriptionService $serviceDescriptionService,
        Template $template,
        TranslationManager $translationManager,
        UserServiceRepository $userServiceRepository,
        UserServiceService $userServiceService,
        Service $service = null
    ) {
        parent::__construct($template, $serviceDescriptionService, $service);
        $this->licenseServerService = $licenseServerService;
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->userServiceService = $userServiceService;
        $this->priceTextService = $priceTextService;
        $this->engineService = $engineService;
        $this->userServiceRepository = $userServiceRepository;
        $this->lang = $translationManager->user();
    }

    public function purchaseFormGet(array $query): string
    {
        return "";
    }

    public function purchaseFormValidate(Purchase $purchase, array $body): void
    {
        //
    }

    public function orderDetails(Purchase $purchase): string
    {
        $statement = $this->db->statement(
            "SELECT `identifier` FROM `{$this->getUserServiceTable()}` WHERE `us_id` = ?"
        );
        $statement->execute([$purchase->getOrder("user_service_id")]);
        $identifier = $statement->fetchColumn();

        return $this->template->renderNoComments(
            "shop/services/shopsms_license_edit/order_details",
            [
                "costMonthly" => $this->priceTextService->getPriceText(
                    $purchase->getOrder("cost_daily") * 30
                ),
                "email" => $purchase->getEmail() ?: $this->lang->t("none"),
                "engines" => $this->engineService->formatOrderEngines(
                    $purchase->getOrder("engines")
                ),
                "identifier" => $identifier,
                "serviceName" => $this->service->getName(),
            ]
        );
    }

    public function purchase(Purchase $purchase): int
    {
        $userService = $this->userServiceService->findOne($purchase->getOrder("user_service_id"));
        $engines = $purchase->getOrder("engines");

        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $promoCode = $purchase->getPromoCode();

        $this->licenseServerService->updatePlatforms(
            $userService->getExternalLicenseId(),
            $engines["amxx"],
            $engines["sm"]
        );

        $this->userServiceRepository->updateWithModule(
            $this->getUserServiceTable(),
            $purchase->getOrder("user_service_id"),
            [
                "cost_daily" => $purchase->getOrder("cost_daily"),
                "email" => $purchase->getEmail(),
                "platform_amxmodx" => $engines["amxx"],
                "platform_sourcemod" => $engines["sm"],
            ]
        );

        return $this->boughtServiceService->create(
            $purchase->user->getId(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            (string) $purchase->getPaymentOption()->getPaymentMethod(),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            0,
            $userService->getIdentifier(),
            $purchase->getEmail(),
            $promoCode ? $promoCode->getCode() : null,
            ["engines" => $this->engineService->formatOrderEngines($engines)]
        );
    }

    public function purchaseInfo($action, Transaction $transaction)
    {
        $engines = $transaction->getExtraDatum("engines");

        if ($action === "email") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license_edit/purchase_info_email",
                [
                    "authData" => $transaction->getAuthData(),
                    "engines" => $engines,
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license_edit/purchase_info_web",
                [
                    "authData" => $transaction->getAuthData(),
                    "email" => $transaction->getEmail(),
                    "engines" => $engines,
                ]
            );
        }

        if ($action === "payment_log") {
            return [
                "text" => $this->lang->t("license_edited", $transaction->getAuthData()),
                "class" => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function showOnWeb(): bool
    {
        return false;
    }
}
