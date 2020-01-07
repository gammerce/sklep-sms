<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidPaymentModuleException;
use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Loggers\DatabaseLogger;
use App\Repositories\PaymentPlatformRepository;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentPlatformCollection
{
    public function post(
        Request $request,
        PaymentPlatformRepository $repository,
        TranslationManager $translationManager,
        PaymentPlatformService $paymentPlatformService,
        DatabaseLogger $databaseLogger
    ) {
        $lang = $translationManager->user();
        $name = $request->request->get("name");
        $moduleId = $request->request->get("module");
        $data = $request->request->get("data") ?: [];

        try {
            $filteredData = $paymentPlatformService->getValidatedData($moduleId, $data);
        } catch (InvalidPaymentModuleException $e) {
            throw new ValidationException([
                "module" => $lang->t("invalid_payment_module"),
            ]);
        }

        $paymentPlatform = $repository->create($name, $moduleId, $filteredData);

        $databaseLogger->logWithActor('log_payment_platform_added', $paymentPlatform->getId());

        return new SuccessApiResponse($lang->t('payment_platform_added'), [
            'data' => [
                'id' => $paymentPlatform->getId(),
            ],
        ]);
    }
}
