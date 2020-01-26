<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Repositories\AntiSpamQuestionRepository;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class AntiSpamQuestionRule implements Rule
{
    /** @var Translator */
    private $lang;

    /** * @var AntiSpamQuestionRepository */
    private $antiSpamQuestionRepository;

    public function __construct(
        AntiSpamQuestionRepository $antiSpamQuestionRepository,
        TranslationManager $translationManager
    ) {
        $this->lang = $translationManager->user();
        $this->antiSpamQuestionRepository = $antiSpamQuestionRepository;
    }

    public function validate($attribute, $value, array $data)
    {
        $asId = $data["as_id"];
        $asAnswer = $data["as_answer"];

        $antiSpamQuestion = $this->antiSpamQuestionRepository->get($asId);

        if (!in_array(strtolower($asAnswer), $antiSpamQuestion->getAnswers())) {
            return [$this->lang->t('wrong_anti_answer')];
        }

        return [];
    }
}
