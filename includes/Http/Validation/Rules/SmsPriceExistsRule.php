<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\SmsPriceRepository;

class SmsPriceExistsRule extends BaseRule
{
    /** @var SmsPriceRepository */
    private $smsPriceRepository;

    public function __construct()
    {
        parent::__construct();
        $this->smsPriceRepository = app()->make(SmsPriceRepository::class);
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->smsPriceRepository->exists($value)) {
            return [$this->lang->t('invalid_price')];
        }

        return [];
    }
}
