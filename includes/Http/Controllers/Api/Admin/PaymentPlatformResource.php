<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Loggers\DatabaseLogger;
use App\Managers\ServerManager;
use App\Models\Server;
use App\Repositories\PaymentPlatformRepository;
use App\System\Settings;
use App\Translation\TranslationManager;
use App\Verification\Exceptions\ProcessDataFieldsException;
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

        try {
            $processedData = $paymentPlatformService->processDataFields(
                $paymentPlatform->getModuleId(),
                $data
            );
        } catch (ProcessDataFieldsException $e) {
            throw new ValidationException([
                "module" => $e->getMessage(),
            ]);
        }

        $paymentPlatformRepository->update($paymentPlatform->getId(), $name, $processedData);
        $databaseLogger->logWithActor("log_payment_platform_edited", $paymentPlatform->getId());

        return new SuccessApiResponse($lang->t("payment_platform_updated"));
    }

    public function delete(
        $paymentPlatformId,
        TranslationManager $translationManager,
        PaymentPlatformRepository $paymentPlatformRepository,
        Settings $settings,
        ServerManager $serverManager,
        DatabaseLogger $databaseLogger
    ) {
        $paymentPlatform = $paymentPlatformRepository->getOrFail($paymentPlatformId);

        $lang = $translationManager->user();

        if (
            $settings->getSmsPlatformId() === $paymentPlatform->getId() ||
            in_array($paymentPlatform->getId(), $settings->getTransferPlatformIds(), true) ||
            $settings->getDirectBillingPlatformId() === $paymentPlatform->getId()
        ) {
            return new ErrorApiResponse($lang->t("delete_payment_platform_settings_constraint"));
        }

        $occupiedPlatforms = collect($serverManager->all())->flatMap(
            fn(Server $server) => array_merge(
                [$server->getSmsPlatformId()],
                $server->getTransferPlatformIds()
            )
        );

        if ($occupiedPlatforms->includes($paymentPlatform->getId())) {
            return new ErrorApiResponse($lang->t("delete_payment_platform_server_constraint"));
        }

        $paymentPlatformRepository->delete($paymentPlatform->getId());
        $databaseLogger->logWithActor("log_payment_platform_deleted", $paymentPlatformId);

        return new SuccessApiResponse($lang->t("payment_platform_deleted"));
    }
}
