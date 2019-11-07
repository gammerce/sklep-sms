<?php
namespace App\Http\Validation\Rules;

use App\TranslationManager;
use App\Translator;
use App\Http\Validation\Rule;

class RequiredRule implements Rule
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
            return [$this->lang->translate('field_no_empty')];
        }

        return [];
    }
}
