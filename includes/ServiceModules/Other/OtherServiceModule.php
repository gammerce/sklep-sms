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
     * @param Purchase $purchase
     * @return array
     */
    public function purchaseDataValidate(Purchase $purchase)
    {
        $warnings = [];

        // Serwer
        if (!strlen($purchase->getOrder('server'))) {
            $warnings['server'][] = $this->lang->t('must_choose_server');
        } else {
            // Sprawdzanie czy serwer o danym id istnieje w bazie
            $server = $this->heart->getServer($purchase->getOrder('server'));
            if (!$this->heart->serverServiceLinked($server->getId(), $this->service->getId())) {
                $warnings['server'][] = $this->lang->t('chosen_incorrect_server');
            }
        }

        // Wartość usługi
        $price = $purchase->getPrice();
        if (!$price) {
            // TODO Replace 'value' with 'price_id' everywhere
            $warnings['price_id'][] = $this->lang->t('must_choose_quantity');
        } elseif (
            !$price->concernService($this->service) ||
            (isset($server) && !$price->concernServer($server))
        ) {
            return [
                'status' => "no_option",
                'text' => $this->lang->t('service_not_affordable'),
                'positive' => false,
            ];
        }

        // E-mail
        if (
            strlen($purchase->getEmail()) &&
            ($warning = check_for_warnings("email", $purchase->getEmail()))
        ) {
            $warnings['email'] = array_merge((array) $warnings['email'], $warning);
        }

        if ($warnings) {
            return [
                'status' => "warnings",
                'text' => $this->lang->t('form_wrong_filled'),
                'positive' => false,
                'data' => compact('warnings'),
            ];
        }

        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $price->getQuantity(),
            Purchase::ORDER_FOREVER => $price->isForever(),
        ]);

        $purchase->setPayment([
            Purchase::PAYMENT_TRANSFER_PRICE => $purchase->getPrice()->getTransferPrice(),
        ]);

        return [
            'status' => "ok",
            'text' => $this->lang->t('purchase_form_validated'),
            'positive' => true,
            'purchase_data' => $purchase,
        ];
    }

    public function purchase(Purchase $purchase)
    {
        return $this->boughtServiceService->create(
            $purchase->user->getUid(),
            $purchase->user->getUsername(),
            $purchase->user->getLastIp(),
            $purchase->getPayment('method'),
            $purchase->getPayment('payment_id'),
            $this->service->getId(),
            $purchase->getOrder('server'),
            $purchase->getOrder(Purchase::ORDER_QUANTITY),
            $purchase->getOrder('auth_data'),
            $purchase->getEmail()
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