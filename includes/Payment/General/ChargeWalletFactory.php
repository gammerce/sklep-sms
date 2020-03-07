<?php
namespace App\Payment\General;

use App\Models\Purchase;
use App\Payment\Interfaces\IChargeWallet;
use App\Payment\DirectBilling\DirectBillingChargeWallet;
use App\Payment\Sms\SmsChargeWallet;
use App\Payment\Transfer\TransferChargeWallet;
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
     * @return IChargeWallet[]
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
