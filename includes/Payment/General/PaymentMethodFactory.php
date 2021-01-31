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
    private Application $app;

    /** @var string[] */
    private array $paymentMethodsClasses;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->paymentMethodsClasses = [
            PaymentMethod::SMS => SmsPaymentMethod::class,
            PaymentMethod::DIRECT_BILLING => DirectBillingPaymentMethod::class,
            PaymentMethod::TRANSFER => TransferPaymentMethod::class,
            PaymentMethod::WALLET => WalletPaymentMethod::class,
        ];
    }

    /**
     * @param PaymentMethod $paymentMethod
     * @return IPaymentMethod
     * @throws UnexpectedValueException
     */
    public function create(PaymentMethod $paymentMethod): IPaymentMethod
    {
        if (isset($this->paymentMethodsClasses[$paymentMethod->getValue()])) {
            return $this->app->make($this->paymentMethodsClasses[$paymentMethod->getValue()]);
        }

        throw new UnexpectedValueException("Payment method [$paymentMethod] doesn't exist");
    }
}
