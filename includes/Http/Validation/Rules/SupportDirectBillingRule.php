<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\System\Heart;
use App\Verification\Abstracts\SupportDirectBilling;

class SupportDirectBillingRule extends BaseRule
{
    /** @var Heart */
    private $heart;

    public function __construct()
    {
        parent::__construct();
        $this->heart = app()->make(Heart::class);
    }

    public function validate($attribute, $value, array $data)
    {
        $paymentModule = $this->heart->getPaymentModuleByPlatformId($value);

        if (!($paymentModule instanceof SupportDirectBilling)) {
            return [$this->lang->t('no_direct_billing_platform')];
        }

        return [];
    }
}
