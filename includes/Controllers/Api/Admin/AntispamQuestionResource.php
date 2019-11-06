<?php
namespace App\Controllers\Api\Admin;

use App\Auth;
use App\Database;
use App\Responses\ApiResponse;
use App\TranslationManager;

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
}
