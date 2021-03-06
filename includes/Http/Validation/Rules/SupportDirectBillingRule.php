<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\PaymentModuleManager;
use App\Verification\Abstracts\SupportDirectBilling;

class SupportDirectBillingRule extends BaseRule
{
    private PaymentModuleManager $paymentModuleManager;

    public function __construct()
    {
        parent::__construct();
        $this->paymentModuleManager = app()->make(PaymentModuleManager::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        $paymentModule = $this->paymentModuleManager->getByPlatformId($value);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            throw new ValidationException($this->lang->t("no_direct_billing_platform"));
        }
    }
}
