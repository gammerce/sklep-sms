<?php
namespace App\View\Pages;

use App\Payment\General\PaymentMethodFactory;
use App\Payment\General\PurchaseSerializer;
use App\Payment\Interfaces\IPaymentMethod;
use App\ServiceModules\Interfaces\IServicePurchaseWeb;
use App\System\Settings;

class PagePayment extends Page
{
    const PAGE_ID = 'payment';

    /** @var PurchaseSerializer */
    private $purchaseSerializer;

    /** @var Settings */
    private $settings;

    /** @var PaymentMethodFactory */
    private $paymentMethodFactory;

    public function __construct(
        PurchaseSerializer $purchaseSerializer,
        PaymentMethodFactory $paymentMethodFactory,
        Settings $settings
    ) {
        parent::__construct();

        $this->purchaseSerializer = $purchaseSerializer;
        $this->heart->pageTitle = $this->title = $this->lang->t('title_payment');
        $this->settings = $settings;
        $this->paymentMethodFactory = $paymentMethodFactory;
    }

    protected function content(array $query, array $body)
    {
        $sign = array_get($body, 'sign');
        $data = array_get($body, 'data');

        // Check form sign
        if ($sign !== md5($data . $this->settings->getSecret())) {
            return $this->lang->t('wrong_sign');
        }

        $purchase = $this->purchaseSerializer->deserializeAndDecode($data);
        if (!$purchase) {
            return $this->lang->t('error_occurred');
        }

        $serviceModule = $this->heart->getServiceModule($purchase->getServiceId());
        if (!($serviceModule instanceof IServicePurchaseWeb)) {
            return $this->lang->t('bad_module');
        }

        $orderDetails = $serviceModule->orderDetails($purchase);

        $renderers = $this->paymentMethodFactory->createAll();

        $paymentMethods = collect($renderers)
            ->filter(function (IPaymentMethod $renderer) use ($purchase) {
                return $renderer->isAvailable($purchase);
            })
            ->map(function (IPaymentMethod $renderer) use ($purchase) {
                return $renderer->render($purchase);
            })
            ->join();

        return $this->template->render("payment/payment_form", [
            'orderDetails' => $orderDetails,
            'paymentMethods' => $paymentMethods,
            'purchaseData' => $data,
            'purchaseSign' => $sign,
        ]);
    }
}
