<?php
namespace App\Http\Validation\Rules;

use App\Heart;
use App\TranslationManager;
use App\Translator;
use App\Http\Validation\Rule;

class UserGroupsRule implements Rule
{
    /** @var Heart */
    private $heart;

    /** @var Translator */
    private $lang;

    public function __construct(Heart $heart, TranslationManager $translationManager)
    {
        $this->heart = $heart;
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!is_array($value)) {
            return ["Invalid type"];
        }

        foreach ($value as $gid) {
            if (is_null($this->heart->getGroup($gid))) {
                return [$this->lang->translate('wrong_group')];
            }
        }

        return [];
    }
}
