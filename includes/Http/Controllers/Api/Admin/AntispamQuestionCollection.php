<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\Repositories\AntispamQuestionRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class AntispamQuestionCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        AntispamQuestionRepository $repository
    ) {
        $lang = $translationManager->user();

        $question = $request->request->get("question");
        $answers = $request->request->get("answers");

        $warnings = [];

        // Pytanie
        if (!$question) {
            $warnings['question'][] = $lang->t('field_no_empty');
        }

        // Odpowiedzi
        if (!$answers) {
            $warnings['answers'][] = $lang->t('field_no_empty');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $repository->create($question, $answers);

        return new SuccessApiResponse($lang->t('antispam_add'));
    }
}
