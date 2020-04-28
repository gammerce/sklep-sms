<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Verification\Abstracts\SupportDirectBilling;
use App\Managers\PaymentModuleManager;

class SupportDirectBillingRule extends BaseRule
{
    /** @var PaymentModuleManager */
    private $paymentModuleManager;

    public function __construct()
    {
        parent::__construct();
        $this->paymentModuleManager = app()->make(PaymentModuleManager::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId($value);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return [$this->lang->t('no_direct_billing_platform')];
        }

        return [];
    }
}
