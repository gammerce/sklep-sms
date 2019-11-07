<?php
namespace App\Http\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\TranslationManager;
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

        $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "antispam_questions` WHERE `id` = '%d'", [
                $antispamQuestionId,
            ])
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('question_delete'),
                    $user->getUsername(),
                    $user->getUid(),
                    $antispamQuestionId
                )
            );
            return new ApiResponse('ok', $lang->translate('delete_antispamq'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_antispamq'), 0);
    }

    public function put($antispamQuestionId, Request $request, Database $db, TranslationManager $translationManager, Auth $auth)
    {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $question = $request->request->get("question");
        $answers = $request->request->get("answers");

        $warnings = [];

        // Pytanie
        if (!$question) {
            $warnings['question'][] = $lang->translate('field_no_empty');
        }

        // Odpowiedzi
        if (!$answers) {
            $warnings['answers'][] = $lang->translate('field_no_empty');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $db->query(
            $db->prepare(
                "UPDATE `" .
                TABLE_PREFIX .
                "antispam_questions` " .
                "SET `question` = '%s', `answers` = '%s' " .
                "WHERE `id` = '%d'",
                [$question, $answers, $antispamQuestionId]
            )
        );

        if ($db->affectedRows()) {
            log_info(
                $langShop->sprintf(
                    $langShop->translate('question_edit'),
                    $user->getUsername(),
                    $user->getUid(),
                    $antispamQuestionId
                )
            );
            return new ApiResponse('ok', $lang->translate('antispam_edit'), 1);
        }

        return new ApiResponse("not_edited", $lang->translate('antispam_no_edit'), 0);
    }
}
