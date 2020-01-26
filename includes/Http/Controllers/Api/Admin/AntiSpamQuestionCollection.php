<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Validation\WarningBag;
use App\Repositories\AntiSpamQuestionRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class AntiSpamQuestionCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        AntiSpamQuestionRepository $repository
    ) {
        $lang = $translationManager->user();

        $question = $request->request->get("question");
        $answers = $request->request->get("answers");

        $warnings = new WarningBag();

        if (!$question) {
            $warnings->add('question', $lang->t('field_no_empty'));
        }

        if (!$answers) {
            $warnings->add('answers', $lang->t('field_no_empty'));
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $repository->create($question, $answers);

        return new SuccessApiResponse($lang->t('antispam_add'));
    }
}
