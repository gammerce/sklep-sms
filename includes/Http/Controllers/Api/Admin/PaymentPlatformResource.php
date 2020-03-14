<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Loggers\DatabaseLogger;
use App\Repositories\PaymentPlatformRepository;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentPlatformResource
{
    public function put(
        $paymentPlatformId,
        Request $request,
        TranslationManager $translationManager,
        PaymentPlatformService $paymentPlatformService,
        PaymentPlatformRepository $paymentPlatformRepository,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();
        $name = $request->request->get("name");
        $data = $request->request->get("data") ?: [];

        $paymentPlatform = $paymentPlatformRepository->getOrFail($paymentPlatformId);
        $filteredData = $paymentPlatformService->getValidatedData(
            $paymentPlatform->getModuleId(),
            $data
        );
        $paymentPlatformRepository->update($paymentPlatform->getId(), $name, $filteredData);

        $databaseLogger->logWithActor('log_payment_platform_edited', $paymentPlatform->getId());

        return new SuccessApiResponse($lang->t('payment_platform_updated'));
    }

    public function delete(
        $paymentPlatformId,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        Settings $settings,
        Heart $heart,
        DatabaseLogger $databaseLogger
    ) {
        $paymentPlatform = $paymentPlatformRepository->getOrFail($paymentPlatformId);

        $lang = $translationManager->user();

        if (
            $settings->getSmsPlatformId() === $paymentPlatform->getId() ||
            $settings->getTransferPlatformId() === $paymentPlatform->getId() ||
            $settings->getDirectBillingPlatformId() === $paymentPlatform->getId()
        ) {
            return new ErrorApiResponse($lang->t('delete_payment_platform_settings_constraint'));
        }

        foreach ($heart->getServers() as $server) {
            if ($server->getSmsPlatformId() === $paymentPlatform->getId()) {
                return new ErrorApiResponse($lang->t('delete_payment_platform_server_constraint'));
            }
        }

        $paymentPlatformRepository->delete($paymentPlatform->getId());

        $databaseLogger->logWithActor('log_payment_platform_deleted', $paymentPlatformId);

        return new SuccessApiResponse($lang->t('payment_platform_deleted'));
    }
}
