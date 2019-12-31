<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Http\Responses\ApiResponse;
use App\Repositories\PaymentPlatformRepository;
use App\System\Auth;
use App\Translation\TranslationManager;

class PaymentPlatformResource
{
    public function put($paymentPlatformId)
    {
        // TODO Implement
        // TODO Validate data
    }

    public function delete(
        $paymentPlatformId,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        Auth $auth
    ) {
        // TODO Check if servers or settings are using it

        $paymentPlatform = $paymentPlatformRepository->get($paymentPlatformId);
        if (!$paymentPlatform) {
            throw new EntityNotFoundException();
        }

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
