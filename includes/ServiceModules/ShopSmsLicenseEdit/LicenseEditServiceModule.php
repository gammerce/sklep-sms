<?php
namespace App\ServiceModules\ShopSmsLicenseEdit;

use App\Models\LicenseUserService;
use App\Services\LicenseServerService;
use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\ServiceModules\ServiceModule;
use App\Services\UserServiceService;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use UnexpectedValueException;

class LicenseEditServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseWeb
{
    const MODULE_ID = "shopsms_license_edit";
    const USER_SERVICE_TABLE = "ss_user_service_shopsms_license";

    /** @var Translator */
    private $lang;

    /** @var Settings */
    private $settings;

    /** @var LicenseServerService */
    private $licenseServerService;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var UserServiceService */
    private $userServiceService;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->settings = $this->app->make(Settings::class);
        $this->licenseServerService = $this->app->make(LicenseServerService::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->userServiceService = $this->app->make(UserServiceService::class);
    }

    public function purchaseFormGet(array $query)
    {
        //
    }

    public function purchaseFormValidate(Purchase $purchase, array $body)
    {
        //
    }

    public function orderDetails(Purchase $purchase)
    {
        $table = $this::USER_SERVICE_TABLE;
        $statement = $this->db->statement("SELECT `identifier` FROM `$table` WHERE `us_id` = ?");
        $statement->execute([$purchase->getOrder('user_service_id')]);
        $identifier = $statement->fetchColumn();

        $email = $purchase->getEmail() ?: $this->lang->t('none');
        $costMonthly =
            number_format(($purchase->getOrder('cost_daily') * 30) / 100, 2) .
            " " .
            $this->settings->getCurrency();

        $engines = [];
        $tmpEngines = $purchase->getOrder('engines');
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        $engines = !empty($engines) ? implode(", ", $engines) : $this->lang->t('none');

        return $this->template->render(
            "services/shopsms_license_edit/order_details",
            compact('identifier', 'engines', 'costMonthly', 'email') + [
                'serviceName' => $this->service->getName(),
            ],
            true,
            false
        );
    }

    public function purchase(Purchase $purchase)
    {
        $userService = $this->userServiceService->findOne($purchase->getOrder('user_service_id'));
        $tmpEngines = $purchase->getOrder('engines');

        if (!($userService instanceof LicenseUserService)) {
            throw new UnexpectedValueException();
        }

        $this->licenseServerService->updatePlatforms(
            $userService->getExternalLicenseId(),
            $tmpEngines['amxx'],
            $tmpEngines['sm']
        );

        // Aktualizujemy dane licencji w liscie uslug graczy
        $updateData = [
            [
                'column' => 'cost_daily',
                'value' => "'%d'",
                'data' => [$purchase->getOrder('cost_daily')],
            ],
            [
                'column' => 'email',
                'value' => "'%s'",
                'data' => [$purchase->getEmail()],
            ],
            [
                'column' => 'platform_amxmodx',
                'value' => "'%d'",
                'data' => [$tmpEngines['amxx']],
            ],
            [
                'column' => 'platform_sourcemod',
                'value' => "'%d'",
                'data' => [$tmpEngines['sm']],
            ],
        ];
        $this->updateUserService(
            $updateData,
            $purchase->getOrder('user_service_id'),
            $purchase->getOrder('user_service_id')
        );

        // Dodanie informacji o zakupie usługi
        $engines = [];
        if ($tmpEngines['amxx']) {
            $engines[] = "AMX Mod X";
        }
        if ($tmpEngines['sm']) {
            $engines[] = "SOURCEMOD";
        }

        $engines = !empty($engines) ? implode(", ", $engines) : $this->lang->t('none');

        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment(Purchase::PAYMENT_METHOD),
            $purchase->getPayment(Purchase::PAYMENT_PAYMENT_ID),
            $this->service->getId(),
            0,
            0,
            $userService->getIdentifier(),
            $purchase->getEmail(),
            ['engines' => $engines]
        );
    }

    public function purchaseInfo($action, array $data)
    {
        $data['extra_data'] = json_decode($data['extra_data'], true);
        $engines = $data['extra_data']['engines'];

        if ($action == "email") {
            return $this->template->render(
                "services/shopsms_license_edit/purchase_info_email",
                compact('data', 'engines'),
                true,
                false
            );
        }

        if ($action == "web") {
            $email = $data['email'];
            return $this->template->render(
                "services/shopsms_license_edit/purchase_info_web",
                compact('data', 'email', 'engines'),
                true,
                false
            );
        }

        if ($action == "payment_log") {
            return [
                'text' => $this->lang->t('license_edited', $data['auth_data']),
                'class' => "outcome",
            ];
        }

        throw new UnexpectedValueException();
    }

    public function showOnWeb()
    {
        return false;
    }
}
