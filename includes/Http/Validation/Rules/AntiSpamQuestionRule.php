<?php
namespace App\Http\Validation\Rules;

use App\Http\Validation\BaseRule;
use App\Repositories\AntiSpamQuestionRepository;

class AntiSpamQuestionRule extends BaseRule
{
    /** @var AntiSpamQuestionRepository */
    private $antiSpamQuestionRepository;

    public function __construct()
    {
        parent::__construct();
        $this->antiSpamQuestionRepository = app()->make(AntiSpamQuestionRepository::class);
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
