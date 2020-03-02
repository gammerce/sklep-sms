<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use InvalidArgumentException;

class PaymentService
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    public function __construct(
        Heart $heart,
        TranslationManager $translationManager,
        PaymentMethodFactory $paymentMethodFactory
    ) {
        $this->heart = $heart;
        $this->lang = $translationManager->user();
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    public function makePayment(Purchase $purchase)
    {
        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());

        if (!$serviceModule) {
            return [
                'status' => "wrong_module",
                'text' => $this->lang->t('bad_module'),
                'positive' => false,
            ];
        }

        try {
            $paymentMethod = $this->paymentMethodFactory->create(
                $purchase->getPayment(Purchase::PAYMENT_METHOD)
            );
        } catch (InvalidArgumentException $e) {
            return [
                'status' => "wrong_method",
                'text' => $this->lang->t('wrong_payment_method'),
                'positive' => false,
            ];
        }

        return $paymentMethod->pay($purchase, $serviceModule);
    }
}
