<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class AntispamQuestionResource
{
    public function delete(
        $antispamQuestionId,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "antispam_questions` WHERE `id` = '%d'", [
                $antispamQuestionId,
            ])
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->t(
                    'question_delete',
                    $user->getUsername(),
                    $user->getUid(),
                    $antispamQuestionId
                )
            );
            return new SuccessApiResponse($lang->t('delete_antispamq'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_antispamq'), 0);
    }

    public function put(
        $antispamQuestionId,
        Request $request,
        Database $db,
        TranslationManager $translationManager,
        Auth $auth
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

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

        $statement = $db->query(
            $db->prepare(
                "UPDATE `" .
                    TABLE_PREFIX .
                    "antispam_questions` " .
                    "SET `question` = '%s', `answers` = '%s' " .
                    "WHERE `id` = '%d'",
                [$question, $answers, $antispamQuestionId]
            )
        );

        if ($statement->rowCount()) {
            log_to_db(
                $langShop->t(
                    'question_edit',
                    $user->getUsername(),
                    $user->getUid(),
                    $antispamQuestionId
                )
            );
            return new SuccessApiResponse($lang->t('antispam_edit'));
        }

        return new ApiResponse("not_edited", $lang->t('antispam_no_edit'), 0);
    }
}
