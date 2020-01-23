<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\System\Database;
use App\Translation\TranslationManager;

class LogResource
{
    public function delete($logId, Database $db, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `ss_logs` " . "WHERE `id` = '%d'", [$logId])
        );

        if ($statement->rowCount()) {
            return new SuccessApiResponse($lang->t('delete_log'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_log'), 0);
    }
}
