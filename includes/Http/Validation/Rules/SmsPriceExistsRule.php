<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\SmsPriceRepository;

class SmsPriceExistsRule extends BaseRule
{
    private SmsPriceRepository $smsPriceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->smsPriceRepository = app()->make(SmsPriceRepository::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$this->smsPriceRepository->exists(as_money($value))) {
            throw new ValidationException($this->lang->t("invalid_price"));
        }
    }
}
