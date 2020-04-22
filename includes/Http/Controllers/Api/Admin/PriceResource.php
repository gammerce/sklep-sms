<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PriceService;
use App\Loggers\DatabaseLogger;
use App\Repositories\PriceRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PriceResource
{
    public function put(
        $priceId,
        Request $request,
        TranslationManager $translationManager,
        PriceService $priceService,
        PriceRepository $priceRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $validator = $priceService->createValidator($request->request->all());
        $validated = $validator->validateOrFail();

        $updated = $priceRepository->update(
            $priceId,
            $validated["service_id"],
            $validated["server_id"],
            $validated["sms_price"],
            $validated["transfer_price"],
            $validated["direct_billing_price"],
            $validated["quantity"],
            $validated["discount"]
        );

        if ($updated) {
            $logger->logWithActor("log_price_edited", $priceId);
            return new SuccessApiResponse($lang->t("price_edit"));
        }

        return new ApiResponse("not_edited", $lang->t("price_no_edit"), 0);
    }

    public function delete(
        $priceId,
        PriceRepository $priceRepository,
        TranslationManager $translationManager,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $deleted = $priceRepository->delete($priceId);

        if ($deleted) {
            $logger->logWithActor("log_price_deleted", $priceId);
            return new SuccessApiResponse($lang->t("delete_price"));
        }

        return new ApiResponse("not_deleted", $lang->t("no_delete_price"), 0);
    }
}
