<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\System\Heart;
use App\Verification\Abstracts\SupportTransfer;

class SupportTransferRule extends BaseRule
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

        if (!($paymentModule instanceof SupportTransfer)) {
            return [$this->lang->t('no_transfer_platform')];
        }

        return [];
    }
}
