<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ConfirmedRule implements Rule
{
    /** @var Translator */
    private $lang;

    public function __construct(TranslationManager $translationManager)
    {
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!strlen($value)) {
            return [];
        }

        if ($value !== array_get($data, "{$attribute}_repeat")) {
            return [$this->lang->translate('different_values')];
        }

        return [];
    }
}
