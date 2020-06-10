<?php
namespace App\Payment\General;

use App\Payment\DirectBilling\DirectBillingPaymentMethod;
use App\Payment\Interfaces\IPaymentMethod;
use App\Payment\Sms\SmsPaymentMethod;
use App\Payment\Transfer\TransferPaymentMethod;
use App\Payment\Wallet\WalletPaymentMethod;
use App\System\Application;
use UnexpectedValueException;

class PaymentMethodFactory
{
    /** @var Application */
    private $app;

    /** @var string[] */
    private $paymentMethodsClasses;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->paymentMethodsClasses = [
            PaymentMethod::SMS()->getValue() => SmsPaymentMethod::class,
            PaymentMethod::DIRECT_BILLING()->getValue() => DirectBillingPaymentMethod::class,
            PaymentMethod::TRANSFER()->getValue() => TransferPaymentMethod::class,
            PaymentMethod::WALLET()->getValue() => WalletPaymentMethod::class,
        ];
    }

    /**
     * @param PaymentMethod $paymentMethod
     * @return IPaymentMethod
     * @throws UnexpectedValueException
     */
    public function create(PaymentMethod $paymentMethod)
    {
        if (isset($this->paymentMethodsClasses[$paymentMethod->getValue()])) {
            return $this->app->make($this->paymentMethodsClasses[$paymentMethod->getValue()]);
        }

        throw new UnexpectedValueException("Payment method [$paymentMethod] doesn't exist");
    }
}
