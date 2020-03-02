<?php
namespace App\Payment\ServiceCode;

use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Validator;
use App\Models\Purchase;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServiceServiceCode;
use App\ServiceModules\ServiceModule;
use App\Support\Template;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServiceCodePaymentMethod implements IPaymentMethod
{
    /** @var Template */
    private $template;

    /** @var Heart */
    private $heart;

    /** @var ServiceCodePaymentService */
    private $serviceCodePaymentService;

    /** @var Translator */
    private $lang;

    public function __construct(
        Template $template,
        Heart $heart,
        ServiceCodePaymentService $serviceCodePaymentService,
        TranslationManager $translationManager
    ) {
        $this->template = $template;
        $this->heart = $heart;
        $this->serviceCodePaymentService = $serviceCodePaymentService;
        $this->lang = $translationManager->user();
    }

    public function render(Purchase $purchase)
    {
        return $this->template->render("payment_method_code");
    }

    public function isAvailable(Purchase $purchase)
    {
        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());

        return !$purchase->getPayment(Purchase::PAYMENT_DISABLED_SERVICE_CODE) &&
            $serviceModule instanceof IServiceServiceCode;
    }

    public function pay(Purchase $purchase, ServiceModule $serviceModule)
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

        if (is_array($paymentId)) {
            return $paymentId;
        }

        $purchase->setPayment([
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $boughtServiceId = $serviceModule->purchase($purchase);

        return [
            'status' => "purchased",
            'text' => $this->lang->t('purchase_success'),
            'positive' => true,
            'data' => ['bsid' => $boughtServiceId],
        ];
    }
}
