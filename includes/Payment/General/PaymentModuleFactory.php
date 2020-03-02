<?php
namespace App\Payment\General;

use App\Models\PaymentPlatform;
use App\System\Application;

class PaymentModuleFactory
{
    /** @var Application */
    private $app;

    public function __construct(Application $app)
    {
        $this->app = $app;
    }

    public function create($paymentModuleClass, PaymentPlatform $paymentPlatform)
    {
        return $this->app->makeWith($paymentModuleClass, compact('paymentPlatform'));
    }
}
