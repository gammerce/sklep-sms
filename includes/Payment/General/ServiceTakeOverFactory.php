<?php
namespace App\Payment\General;

use App\Payment\Interfaces\IServiceTakeOver;
use App\Payment\Sms\SmsServiceTakeOver;
use App\Payment\Transfer\TransferServiceTakeOver;
use App\System\Application;
use UnexpectedValueException;

class ServiceTakeOverFactory
{
    private Application $app;

    /** @var string[] */
    private array $paymentMethodsClasses;

    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->paymentMethodsClasses = [
            PaymentMethod::SMS => SmsServiceTakeOver::class,
            PaymentMethod::TRANSFER => TransferServiceTakeOver::class,
        ];
    }

    /**
     * @param PaymentMethod $paymentMethod
     * @return IServiceTakeOver
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
