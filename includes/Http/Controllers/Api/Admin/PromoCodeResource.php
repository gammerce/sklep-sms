<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Loggers\DatabaseLogger;
use App\Repositories\PromoCodeRepository;
use App\Translation\TranslationManager;

class PromoCodeResource
{
    public function delete(
        $promoCodeId,
        TranslationManager $translationManager,
        PromoCodeRepository $promoCodeRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $deleted = $promoCodeRepository->delete($promoCodeId);

        if ($deleted) {
            $logger->logWithActor("log_promo_code_deleted", $promoCodeId);
            return new SuccessApiResponse($lang->t("promo_code_deleted"));
        }

        return new ApiResponse("not_deleted", $lang->t("promo_code_not_deleted"), false);
    }
}
