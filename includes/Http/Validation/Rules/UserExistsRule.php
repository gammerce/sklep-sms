<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Repositories\UserRepository;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class UserExistsRule implements Rule
{
    /** @var UserRepository */
    private $userRepository;

    /** @var Translator */
    private $lang;

    public function __construct()
    {
        $this->userRepository = app()->make(UserRepository::class);
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->userRepository->get($value)) {
            return [$this->lang->t('no_account_id')];
        }

        return [];
    }
}
