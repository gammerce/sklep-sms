<?php
namespace App\Payment\General;

use App\Models\PaymentPlatform;
use App\System\Application;
use App\Verification\Abstracts\PaymentModule;

class PaymentModuleFactory
{
    private Application $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function create($paymentModuleClass, PaymentPlatform $paymentPlatform): PaymentModule
    {
        return $this->app->makeWith($paymentModuleClass, compact("paymentPlatform"));
    }
}
