<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Payment\Interfaces\IChargeWallet;
use App\Payment\Sms\DirectBillingChargeWallet;
use App\Payment\Sms\SmsChargeWallet;
use App\Payment\Sms\TransferChargeWallet;
use App\System\Application;
use InvalidArgumentException;

class ChargeWalletFactory
{
    /** @var Application */
    private $app;

    private $paymentMethodsClasses = [
        Purchase::METHOD_DIRECT_BILLING => DirectBillingChargeWallet::class,
        Purchase::METHOD_SMS => SmsChargeWallet::class,
        Purchase::METHOD_TRANSFER => TransferChargeWallet::class,
    ];

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    /**
     * @param string $paymentMethodId
     * @return IChargeWallet
     */
    public function create($paymentMethodId)
    {
        if (!array_key_exists($paymentMethodId, $this->paymentMethodsClasses)) {
            throw new InvalidArgumentException("Payment method [$paymentMethodId] doesn't exist");
        }

        return $this->app->make($this->paymentMethodsClasses[$paymentMethodId]);
    }
}
