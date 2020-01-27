<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class MaxLengthRule implements Rule
{
    /** @var int */
    private $length;

    /** @var Translator */
    private $lang;

    public function __construct($length)
    {
        $this->length = $length;
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (strlen($value) > $this->length) {
            return [$this->lang->t('max_length')];
        }

        return [];
    }
}
