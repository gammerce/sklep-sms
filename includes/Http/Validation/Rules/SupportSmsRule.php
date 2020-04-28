<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Verification\Abstracts\SupportSms;
use App\Managers\PaymentModuleManager;

class SupportSmsRule extends BaseRule
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

        if (!($paymentModule instanceof SupportSms)) {
            return [$this->lang->t('no_sms_platform')];
        }

        return [];
    }
}
