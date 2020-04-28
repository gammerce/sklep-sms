<?php
namespace App\Payment\ServiceCode;

use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchase;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\Support\Result;
use App\Support\Template;
use App\Translation\TranslationManager;
use App\Translation\Translator;
use App\Managers\ServiceModuleManager;

class ServiceCodePaymentMethod implements IPaymentMethod
{
    /** @var Template */
    private $template;

    /** @var ServiceCodePaymentService */
    private $serviceCodePaymentService;

    /** @var Translator */
    private $lang;

    /** @var ServiceModuleManager */
    private $serviceModuleManager;

    public function __construct(
        Template $template,
        ServiceModuleManager $serviceModuleManager,
        ServiceCodePaymentService $serviceCodePaymentService,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->serviceCodePaymentService = $serviceCodePaymentService;
        $this->lang = $translationManager->user();
        $this->serviceModuleManager = $serviceModuleManager;
    }

    public function render(Purchase $purchase)
    {
        return $this->template->render("payment/payment_method_code");
    }

    public function isAvailable(Purchase $purchase)
    {
        $serviceModule = $this->serviceModuleManager->get($purchase->getServiceId());

        return !$purchase->getPayment(Purchase::PAYMENT_DISABLED_SERVICE_CODE) &&
            $serviceModule instanceof IServiceServiceCode;
    }

    public function pay(Purchase $purchase, IServicePurchase $serviceModule)
    {
        $validator = new Validator(
            [
                'service_code' => $purchase->getPayment(Purchase::PAYMENT_SERVICE_CODE),
            ],
            [
                'service_code' => [new RequiredRule()],
            ]
        );
        $validator->validateOrFail();

        $paymentId = $this->serviceCodePaymentService->payWithServiceCode($purchase);

        if (!$paymentId) {
            return new Result("wrong_service_code", $this->lang->t('bad_service_code'), false);
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return new Result("purchased", $this->lang->t('purchase_success'), true, [
            'bsid' => $boughtServiceId,
        ]);
    }
}
