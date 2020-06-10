<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Managers\PaymentModuleManager;
use App\Verification\Abstracts\SupportTransfer;

class SupportTransferRule extends BaseRule
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
        foreach (to_array($value) as $item) {
            $paymentModule = $this->paymentModuleManager->getByPlatformId($item);

            if (!($paymentModule instanceof SupportTransfer)) {
                return [$this->lang->t('no_transfer_platform')];
            }
        }

        return [];
    }
}
