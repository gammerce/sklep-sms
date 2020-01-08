<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\ServiceCodeRepository;
use App\Translation\TranslationManager;

class ServiceCodeResource
{
    public function delete(
        $serviceCodeId,
        TranslationManager $translationManager,
        ServiceCodeRepository $serviceCodeRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $deleted = $serviceCodeRepository->delete($serviceCodeId);

        if ($deleted) {
            $logger->logWithActor('log_code_deleted', $serviceCodeId);
            return new SuccessApiResponse($lang->t('code_deleted'));
        }

        return new ApiResponse("not_deleted", $lang->t('code_not_deleted'), 0);
    }
}
