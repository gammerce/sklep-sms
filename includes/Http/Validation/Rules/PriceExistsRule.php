<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\PriceRepository;

class PriceExistsRule extends BaseRule
{
    /** @var PriceRepository */
    private $priceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->priceRepository = app()->make(PriceRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->priceRepository->get($value)) {
            return [$this->lang->t('invalid_price')];
        }

        return [];
    }
}
