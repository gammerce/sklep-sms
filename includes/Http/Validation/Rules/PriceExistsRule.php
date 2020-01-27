<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Repositories\PriceRepository;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class PriceExistsRule implements Rule
{
    /** @var PriceRepository */
    private $priceRepository;

    /** @var Translator */
    private $lang;

    public function __construct()
    {
        $this->priceRepository = app()->make(PriceRepository::class);
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->priceRepository->get($value)) {
            return [$this->lang->t('invalid_price')];
        }

        return [];
    }
}
