<?php
namespace App\Http\Validation;

use App\Translation\TranslationManager;
use App\Translation\Translator;

abstract class BaseRule implements Rule
{
    /** @var Translator */
    protected $lang;

    public function __construct()
    {
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function acceptsEmptyValue()
    {
        return false;
    }

    public function breaksPipelineOnWarning()
    {
        return false;
    }
}
