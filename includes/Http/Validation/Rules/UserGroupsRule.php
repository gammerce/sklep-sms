<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\System\Heart;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class UserGroupsRule implements Rule
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct()
    {
        $this->heart = app()->make(Heart::class);
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return ["Invalid type"];
        }

        foreach ($value as $gid) {
            if ($this->heart->getGroup($gid) === null) {
                return [$this->lang->t('wrong_group')];
            }
        }

        return [];
    }
}
