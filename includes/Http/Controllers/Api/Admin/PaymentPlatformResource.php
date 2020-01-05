<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Repositories\PaymentPlatformRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentPlatformResource
{
    public function put(
        $paymentPlatformId,
        Request $request,
        Auth $auth,
        TranslationManager $translationManager,
        PaymentPlatformService $paymentPlatformService,
        PaymentPlatformRepository $paymentPlatformRepository
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();
        $name = $request->request->get("name");
        $data = $request->request->get("data") ?: [];

        $paymentPlatform = $paymentPlatformRepository->getOrFail($paymentPlatformId);
        $filteredData = $paymentPlatformService->getValidatedData(
            $paymentPlatform->getModuleId(),
            $data
        );
        $paymentPlatformRepository->update($paymentPlatform->getId(), $name, $filteredData);

        log_to_db(
            $langShop->t(
                'log_payment_platform_updated',
                $user->getUsername(),
                $user->getUid(),
                $paymentPlatform->getId()
            )
        );

        return new ApiResponse('ok', $lang->translate('payment_platform_updated'), true);
    }

    public function delete(
        $paymentPlatformId,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        Auth $auth
    ) {
        // TODO Check if servers or settings are using it
        $paymentPlatformRepository->getOrFail($paymentPlatformId);

        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $paymentPlatformRepository->delete($paymentPlatformId);

        log_to_db(
            $langShop->sprintf(
                $langShop->translate('log_payment_platform_deleted'),
                $user->getUsername(),
                $user->getUid(),
                $paymentPlatformId
            )
        );

        return new ApiResponse('ok', $lang->translate('payment_platform_deleted'), true);
    }
}
