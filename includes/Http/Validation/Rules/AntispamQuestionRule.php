<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\Rule;
use App\Repositories\AntispamQuestionRepository;
use App\Translation\TranslationManager;
use App\Translation\Translator;

class AntispamQuestionRule implements Rule
{
    /** @var Translator */
    private $lang;

    /** * @var AntispamQuestionRepository */
    private $antispamQuestionRepository;

    public function __construct(
        AntispamQuestionRepository $antispamQuestionRepository,
        TranslationManager $translationManager
    ) {
        $this->lang = $translationManager->user();
        $this->antispamQuestionRepository = $antispamQuestionRepository;
    }

    public function validate($attribute, $value, array $data)
    {
        $asId = $data["as_id"];
        $asAnswer = $data["as_answer"];

        $antispamQuestion = $this->antispamQuestionRepository->get($asId);

        if (!in_array(strtolower($asAnswer), $antispamQuestion->getAnswers())) {
            return [$this->lang->t('wrong_anti_answer')];
        }

        return [];
    }
}
