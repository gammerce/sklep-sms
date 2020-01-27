<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Models\User;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class UserPasswordRule implements Rule
{
    /** @var Translator */
    private $lang;

    /** @var User */
    private $user;

    public function __construct(User $user)
    {
        /** @var TranslationManager $translationManager */
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
        $this->user = $user;
    }

    public function validate($attribute, $value, array $data)
    {
        if (hash_password($value, $this->user->getSalt()) != $this->user->getPassword()) {
            return [$this->lang->t('old_pass_wrong')];
        }

        return [];
    }
}
