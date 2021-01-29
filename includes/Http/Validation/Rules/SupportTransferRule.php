<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Managers\PaymentModuleManager;
use App\Verification\Abstracts\SupportTransfer;

class SupportTransferRule extends BaseRule
{
    private PaymentModuleManager $paymentModuleManager;

    public function __construct()
    {
        parent::__construct();
        $this->paymentModuleManager = app()->make(PaymentModuleManager::class);
    }

    public function validate($attribute, $value, array $data)
    {
        assert(is_array($value));

        foreach ($value as $item) {
            $paymentModule = $this->paymentModuleManager->getByPlatformId($item);

            if (!($paymentModule instanceof SupportTransfer)) {
                throw new ValidationException($this->lang->t("no_transfer_platform"));
            }
        }
    }
}
