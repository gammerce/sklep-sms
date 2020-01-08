<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\System\Database;
use App\Translation\TranslationManager;

class SmsCodeResource
{
    public function delete(
        $smsCodeId,
        Database $db,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $statement = $db->query(
            $db->prepare("DELETE FROM `" . TABLE_PREFIX . "sms_codes` WHERE `id` = '%d'", [
                $smsCodeId,
            ])
        );

        if ($statement->rowCount()) {
            $logger->logWithActor('log_sms_code_deleted', $smsCodeId);
            return new SuccessApiResponse($lang->t('delete_sms_code'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_sms_code'), 0);
    }
}
