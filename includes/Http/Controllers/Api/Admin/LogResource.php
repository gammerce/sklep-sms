<?php
namespace App\Http\Controllers\Api\Admin;

use App\System\Database;
use App\Http\Responses\ApiResponse;
use App\Translation\TranslationManager;

class LogResource
{
    public function delete($logId, Database $db, TranslationManager $translationManager)
    {
        $lang = $translationManager->user();

        $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "logs` " . "WHERE `id` = '%d'", [$logId])
        );

        // Zwróć info o prawidłowym lub błędnym usunieciu
        if ($db->affectedRows()) {
            return new ApiResponse('ok', $lang->translate('delete_log'), 1);
        }

        return new ApiResponse("not_deleted", $lang->translate('no_delete_log'), 0);
    }
}
