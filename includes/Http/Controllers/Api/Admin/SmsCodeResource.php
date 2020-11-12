<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\SmsCodeRepository;
use App\Translation\TranslationManager;

class SmsCodeResource
{
    public function delete(
        $smsCodeId,
        TranslationManager $translationManager,
        SmsCodeRepository $smsCodeRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $deleted = $smsCodeRepository->delete($smsCodeId);

        if ($deleted) {
            $logger->logWithActor("log_sms_code_deleted", $smsCodeId);
            return new SuccessApiResponse($lang->t("delete_sms_code"));
        }

        return new ApiResponse("not_deleted", $lang->t("no_delete_sms_code"), 0);
    }
}
