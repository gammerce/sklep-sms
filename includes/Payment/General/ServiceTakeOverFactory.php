<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Payment\Interfaces\IServiceTakeOver;
use App\Payment\Sms\SmsServiceTakeOver;
use App\Payment\Transfer\TransferServiceTakeOver;
use App\System\Application;
use InvalidArgumentException;

class ServiceTakeOverFactory
{
    /** @var Application */
    private $app;

    private $paymentMethodsClasses = [
        Purchase::METHOD_SMS => SmsServiceTakeOver::class,
        Purchase::METHOD_TRANSFER => TransferServiceTakeOver::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $paymentMethodId
     * @return IServiceTakeOver
     */
    public function create($paymentMethodId)
    {
        if (!array_key_exists($paymentMethodId, $this->paymentMethodsClasses)) {
            throw new InvalidArgumentException("Payment method [$paymentMethodId] doesn't exist");
        }

        return $this->app->make($this->paymentMethodsClasses[$paymentMethodId]);
    }
}
