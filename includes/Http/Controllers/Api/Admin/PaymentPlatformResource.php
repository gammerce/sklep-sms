<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Repositories\PaymentPlatformRepository;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
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

        return new SuccessApiResponse($lang->translate('payment_platform_updated'));
    }

    public function delete(
        $paymentPlatformId,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        Auth $auth,
        Settings $settings,
        Heart $heart
    ) {
        $paymentPlatform = $paymentPlatformRepository->getOrFail($paymentPlatformId);

        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        if (
            $settings->getSmsPlatformId() === $paymentPlatform->getId() ||
            $settings->getTransferPlatformId() === $paymentPlatform->getId()
        ) {
            return new ErrorApiResponse(
                $lang->translate('delete_payment_platform_settings_constraint')
            );
        }

        foreach ($heart->getServers() as $server) {
            if ($server->getSmsPlatformId() === $paymentPlatform->getId()) {
                return new ErrorApiResponse(
                    $lang->translate('delete_payment_platform_server_constraint')
                );
            }
        }

        $paymentPlatformRepository->delete($paymentPlatform->getId());

        log_to_db(
            $langShop->t(
                'log_payment_platform_deleted',
                $user->getUsername(),
                $user->getUid(),
                $paymentPlatformId
            )
        );

        return new SuccessApiResponse($lang->translate('payment_platform_deleted'));
    }
}
