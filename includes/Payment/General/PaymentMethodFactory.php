<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Payment\DirectBilling\DirectBillingPaymentMethod;
use App\Payment\Interfaces\IPaymentMethod;
use App\Payment\ServiceCode\ServiceCodePaymentMethod;
use App\Payment\Sms\SmsPaymentMethod;
use App\Payment\Transfer\TransferPaymentMethod;
use App\Payment\Wallet\WalletPaymentMethod;
use App\System\Application;
use InvalidArgumentException;

class PaymentMethodFactory
{
    /** @var Application */
    private $app;

    private $paymentMethodsClasses = [
        Purchase::METHOD_DIRECT_BILLING => DirectBillingPaymentMethod::class,
        Purchase::METHOD_SERVICE_CODE => ServiceCodePaymentMethod::class,
        Purchase::METHOD_SMS => SmsPaymentMethod::class,
        Purchase::METHOD_TRANSFER => TransferPaymentMethod::class,
        Purchase::METHOD_WALLET => WalletPaymentMethod::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @return IPaymentMethod[]
     */
    public function createAll()
    {
        return collect($this->paymentMethodsClasses)
            ->map(function ($class) {
                return $this->app->make($class);
            })
            ->all();
    }

    /**
     * @param string $paymentMethodId
     * @return IPaymentMethod
     */
    public function create($paymentMethodId)
    {
        if (!array_key_exists($paymentMethodId, $this->paymentMethodsClasses)) {
            throw new InvalidArgumentException("Payment method [$paymentMethodId] doesn't exist");
        }

        return $this->app->make($this->paymentMethodsClasses[$paymentMethodId]);
    }
}
