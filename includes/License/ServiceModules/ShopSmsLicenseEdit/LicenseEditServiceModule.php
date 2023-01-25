<?php
namespace App\License\ServiceModules\ShopSmsLicenseEdit;

use App\License\Models\LicenseUserService;
use App\License\ProxyManagerClient;
use App\License\SubdomainUtil;
use App\Models\Purchase;
use App\Models\Service;
use App\Models\Transaction;
use App\Payment\General\BoughtServiceService;
use App\Repositories\UserServiceRepository;
use App\Server\Platform;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePromoCode;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\License\ServiceModules\ShopSmsLicense\PlatformService;
use App\License\LicenseServerService;
use App\Support\PriceTextService;
use App\Service\UserServiceService;
use App\Support\Database;
use App\Theme\Template;
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

    private BoughtServiceService $boughtServiceService;
    private Database $db;
    private LicenseServerService $licenseServerService;
    private PlatformService $platformService;
    private PriceTextService $priceTextService;
    private ProxyManagerClient $proxyManagerClient;
    private Translator $lang;
    private UserServiceRepository $userServiceRepository;
    private UserServiceService $userServiceService;

    public function __construct(
        BoughtServiceService $boughtServiceService,
        Database $db,
        LicenseServerService $licenseServerService,
        PlatformService $platformService,
        PriceTextService $priceTextService,
        ProxyManagerClient $proxyManagerClient,
        Template $template,
        TranslationManager $translationManager,
        UserServiceRepository $userServiceRepository,
        UserServiceService $userServiceService,
        Service $service = null
    ) {
        parent::__construct($template, $service);
        $this->boughtServiceService = $boughtServiceService;
        $this->db = $db;
        $this->lang = $translationManager->user();
        $this->licenseServerService = $licenseServerService;
        $this->platformService = $platformService;
        $this->priceTextService = $priceTextService;
        $this->proxyManagerClient = $proxyManagerClient;
        $this->userServiceRepository = $userServiceRepository;
        $this->userServiceService = $userServiceService;
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
        $statement->bindAndExecute([$purchase->getOrder("user_service_id")]);
        $identifier = $statement->fetchColumn();
        $subdomain = $purchase->getOrder(SubdomainUtil::PURCHASE_KEY);

        return $this->template->renderNoComments(
            "shop/services/shopsms_license_edit/order_details",
            [
                "costMonthly" => $this->priceTextService->getPriceText(
                    $purchase->getOrder("cost_daily") * 30
                ),
                "email" => $purchase->getEmail() ?: $this->lang->t("none"),
                "identifier" => $identifier,
                "platforms" => $this->platformService->formatPlatforms(
                    $purchase->getOrder("platforms")
                ),
                "serviceName" => $this->service->getName(),
                "subdomain" => SubdomainUtil::getText($subdomain),
            ]
        );
    }

    public function purchase(Purchase $purchase): int
    {
        $userService = $this->userServiceService->findOne($purchase->getOrder("user_service_id"));
        $platforms = $purchase->getOrder("platforms");

        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $promoCode = $purchase->getPromoCode();
        $subdomain = $purchase->getOrder(SubdomainUtil::PURCHASE_KEY);

        $this->licenseServerService->updatePlatforms($userService->getIdentifier(), $platforms);

        $this->userServiceRepository->updateWithModule(
            $this->getUserServiceTable(),
            $purchase->getOrder("user_service_id"),
            [
                "cost_daily" => $purchase->getOrder("cost_daily"),
                "email" => $purchase->getEmail(),
                "platform_amxmodx" => (int) in_array(Platform::AMXMODX(), $platforms),
                "platform_sourcemod" => (int) in_array(Platform::SOURCEMOD(), $platforms),
                "subdomain" => $subdomain,
            ]
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
            0,
            $userService->getIdentifier(),
            $purchase->getEmail(),
            $promoCode?->getCode(),
            [
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
                "shop/services/shopsms_license_edit/purchase_info_email",
                [
                    "authData" => $transaction->getAuthData(),
                    "platforms" => $platforms,
                    "subdomain" => SubdomainUtil::getText($subdomain),
                ]
            );
        }

        if ($action === "web") {
            return $this->template->renderNoComments(
                "shop/services/shopsms_license_edit/purchase_info_web",
                [
                    "authData" => $transaction->getAuthData(),
                    "email" => $transaction->getEmail(),
                    "platforms" => $platforms,
                    "subdomain" => SubdomainUtil::getText($subdomain),
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
