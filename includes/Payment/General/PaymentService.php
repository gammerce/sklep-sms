<?php
namespace App\Payment\General;

use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Support\Result;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use InvalidArgumentException;

class PaymentService
{
    /** @var Translator */
    private $lang;

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        TranslationManager $translationManager,
        PaymentMethodFactory $paymentMethodFactory,
        ServiceModuleManager $serviceModuleManager
    ) {
        $this->lang = $translationManager->user();
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->serviceModuleManager = $serviceModuleManager;
    }

    /**
     * @param Purchase $purchase
     * @return Result
     */
    public function makePayment(Purchase $purchase)
    {
        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());

        if (!($serviceModule instanceof IServicePurchase)) {
            return new Result("wrong_module", $this->lang->t('bad_module'), false);
        }

        try {
            $paymentMethod = $this->paymentMethodFactory->create(
                $purchase->getPayment(Purchase::PAYMENT_METHOD)
            );
        } catch (InvalidArgumentException $e) {
            return new Result("wrong_method", $this->lang->t('wrong_payment_method'), false);
        }

        return $paymentMethod->pay($purchase, $serviceModule);
    }
}
