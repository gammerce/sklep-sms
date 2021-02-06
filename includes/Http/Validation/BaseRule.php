<?php
namespace App\Http\Validation;

use App\Translation\TranslationManager;
use App\Translation\Translator;

abstract class BaseRule implements Rule
{
    protected Translator $lang;

    public function __construct()
    {
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function acceptsEmptyValue(): bool
    {
        return false;
    }

    public function breaksPipelineOnWarning(): bool
    {
        return false;
    }
}
