<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Repositories\LogRepository;
use App\Translation\TranslationManager;

class LogResource
{
    public function delete(
        $logId,
        LogRepository $repository,
        TranslationManager $translationManager
    ) {
        $lang = $translationManager->user();

        $deleted = $repository->delete($logId);

        if ($deleted) {
            return new SuccessApiResponse($lang->t('delete_log'));
        }

        return new ApiResponse("not_deleted", $lang->t('no_delete_log'), 0);
    }
}
