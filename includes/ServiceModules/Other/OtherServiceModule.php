<?php
namespace App\ServiceModules\Other;

use App\Models\Purchase;
use App\Models\Service;
use App\Payment\BoughtServiceService;
use App\Repositories\PriceRepository;
use App\ServiceModules\Interfaces\IServiceAdminManage;
use App\ServiceModules\Interfaces\IServiceAvailableOnServers;
use App\ServiceModules\Interfaces\IServiceCreate;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\ServiceModules\ServiceModule;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class OtherServiceModule extends ServiceModule implements
    IServicePurchase,
    IServicePurchaseOutside,
    IServiceCreate,
    IServiceAdminManage,
    IServiceAvailableOnServers
{
    const MODULE_ID = "other";

    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var BoughtServiceService */
    private $boughtServiceService;

    /** @var PriceRepository */
    private $priceRepository;

    public function __construct(Service $service = null)
    {
        parent::__construct($service);

        $this->heart = $this->app->make(Heart::class);
        $this->boughtServiceService = $this->app->make(BoughtServiceService::class);
        $this->priceRepository = $this->app->make(PriceRepository::class);
        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    /**
     * @param Purchase $purchaseData
     * @return array
     */
    public function purchaseDataValidate(Purchase $purchaseData)
    {
        $warnings = [];

        // Serwer
        $server = null;
        if (!strlen($purchaseData->getOrder('server'))) {
            $warnings['server'][] = $this->lang->t('must_choose_server');
        } else {
            // Sprawdzanie czy serwer o danym id istnieje w bazie
            $server = $this->heart->getServer($purchaseData->getOrder('server'));
            if (!$this->heart->serverServiceLinked($server->getId(), $this->service->getId())) {
                $warnings['server'][] = $this->lang->t('chosen_incorrect_server');
            }
        }

        // Wartość usługi
        if (!strlen($purchaseData->getTariff())) {
            $warnings['value'][] = $this->lang->t('must_choose_quantity');
        } else {
            // TODO Use smsPrice instead of tariff
            $price = $this->priceRepository->findByServiceServerAndSmsPrice(
                $this->service,
                $server,
                $purchaseData->getTariff()
            );

            if (!$price) {
                return [
                    'status' => "no_option",
                    'text' => $this->lang->t('service_not_affordable'),
                    'positive' => false,
                ];
            }
        }

        // E-mail
        if (
            strlen($purchaseData->getEmail()) &&
            ($warning = check_for_warnings("email", $purchaseData->getEmail()))
        ) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        if ($warnings) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => ['warnings' => $warnings],
            ];
        }

        $purchaseData->setOrder([
            'amount' => $price->getQuantity(),
            'forever' => $price->isForever(),
        ]);

        $purchaseData->setPayment([
            'cost' => $purchaseData->getTariff()->getProvision(),
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchaseData,
        ];
    }

    public function purchase(Purchase $purchaseData)
    {
        return $this->boughtServiceService->create(
            $purchaseData->user->getUid(),
            $purchaseData->user->getUsername(),
            $purchaseData->user->getLastIp(),
            $purchaseData->getPayment('method'),
            $purchaseData->getPayment('payment_id'),
            $this->service->getId(),
            $purchaseData->getOrder('server'),
            $purchaseData->getOrder('amount'),
            $purchaseData->getOrder('auth_data'),
            $purchaseData->getEmail()
        );
    }

    public function serviceAdminManagePost(array $data)
    {
        return [];
    }

    public function serviceAdminExtraFieldsGet()
    {
        return '';
    }

    public function serviceAdminManagePre(array $data)
    {
        return [];
    }
}
