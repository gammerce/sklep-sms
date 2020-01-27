<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Repositories\ServerRepository;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class ServerExistsRule implements Rule
{
    /** @var ServerRepository */
    private $serverRepository;

    /** @var Translator */
    private $lang;

    public function __construct()
    {
        $this->serverRepository = app()->make(ServerRepository::class);
        $translationManager = app()->make(TranslationManager::class);
        $this->lang = $translationManager->user();
    }

    public function validate($attribute, $value, array $data)
    {
        if (!$this->serverRepository->get($value)) {
            return [$this->lang->t('no_server_id')];
        }

        return [];
    }
}
