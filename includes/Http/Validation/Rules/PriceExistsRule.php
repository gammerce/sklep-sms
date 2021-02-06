<?php
namespace App\Http\Validation\Rules;

use App\Exceptions\ValidationException;
use App\Http\Validation\BaseRule;
use App\Repositories\PriceRepository;

class PriceExistsRule extends BaseRule
{
    private PriceRepository $priceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->priceRepository = app()->make(PriceRepository::class);
    }

    public function validate($attribute, $value, array $data): void
    {
        if (!$this->priceRepository->get($value)) {
            throw new ValidationException($this->lang->t("invalid_price"));
        }
    }
}
