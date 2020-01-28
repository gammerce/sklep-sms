<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\PaymentPlatformRepository;

class SmsPlatformExistsRule extends BaseRule
{
    /** @var PaymentPlatformRepository */
    private $paymentPlatformRepository;

    public function __construct()
    {
        parent::__construct();
        $this->paymentPlatformRepository = app()->make(PaymentPlatformRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->paymentPlatformRepository->get($value)) {
            return [$this->lang->t('no_sms_platform')];
        }

        return [];
    }
}
