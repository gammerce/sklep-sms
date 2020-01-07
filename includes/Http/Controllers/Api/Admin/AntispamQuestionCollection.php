<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class AntispamQuestionCollection
{
    public function post(Request $request, Database $db, TranslationManager $translationManager)
    {
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

        $db->query(
            $db->prepare(
                "INSERT INTO `" .
                    TABLE_PREFIX .
                    "antispam_questions` ( question, answers ) " .
                    "VALUES ('%s','%s')",
                [$question, $answers]
            )
        );

        return new SuccessApiResponse($lang->t('antispam_add'));
    }
}
