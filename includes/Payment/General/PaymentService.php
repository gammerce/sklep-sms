<?php
namespace App\Payment\General;

use App\Exceptions\InvalidServiceModuleException;
use App\Exceptions\ValidationException;
use App\Managers\ServiceModuleManager;
use App\Models\Purchase;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Repositories\PromoCodeRepository;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use UnexpectedValueException;

class PaymentService
{
    private Translator $lang;
    private PaymentMethodFactory $paymentMethodFactory;
    private PromoCodeRepository $promoCodeRepository;
    private ServiceModuleManager $serviceModuleManager;

    public function __construct(
        TranslationManager $translationManager,
        PromoCodeRepository $promoCodeRepository,
        PaymentMethodFactory $paymentMethodFactory,
        ServiceModuleManager $serviceModuleManager
    ) {
        $this->lang = $translationManager->user();
        $this->paymentMethodFactory = $paymentMethodFactory;
        $this->serviceModuleManager = $serviceModuleManager;
        $this->promoCodeRepository = $promoCodeRepository;
    }

    /**
     * @param Purchase $purchase
     * @return PaymentResult
     * @throws PaymentProcessingException
     * @throws InvalidServiceModuleException
     * @throws ValidationException
     */
    public function makePayment(Purchase $purchase): PaymentResult
    {
        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());

        if (!($serviceModule instanceof IServicePurchase)) {
            throw new InvalidServiceModuleException();
        }

        try {
            $paymentMethod = $this->paymentMethodFactory->create(
                $purchase->getPaymentOption()->getPaymentMethod()
            );
        } catch (UnexpectedValueException $e) {
            throw new PaymentProcessingException(
                "wrong_method",
                $this->lang->t("wrong_payment_method")
            );
        }

        $paymentResult = $paymentMethod->pay($purchase, $serviceModule);

        $promoCode = $purchase->getPromoCode();
        if ($promoCode) {
            $this->promoCodeRepository->useIt($promoCode->getId());
        }

        return $paymentResult;
    }
}
