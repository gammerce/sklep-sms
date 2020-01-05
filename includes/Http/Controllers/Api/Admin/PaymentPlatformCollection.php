<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidPaymentModuleException;
use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Repositories\PaymentPlatformRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentPlatformCollection
{
    // TODO Action box for creating/editing model

    public function post(
        Request $request,
        PaymentPlatformRepository $repository,
        Auth $auth,
        TranslationManager $translationManager,
        PaymentPlatformService $paymentPlatformService
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();
        $name = $request->request->get("name");
        $moduleId = $request->request->get("module");
        $data = $request->request->get("data") ?: [];

        try {
            $filteredData = $paymentPlatformService->getValidatedData($moduleId, $data);
        } catch (InvalidPaymentModuleException $e) {
            throw new ValidationException([
                "module" => "Invalid module ID",
            ]);
        }

        $paymentPlatform = $repository->create($name, $moduleId, $filteredData);

        log_to_db(
            $langShop->t(
                'log_payment_platform_added',
                $user->getUsername(),
                $user->getUid(),
                $paymentPlatform->getId()
            )
        );

        return new ApiResponse('ok', $lang->translate('payment_platform_added'), true, [
            'data' => [
                'id' => $paymentPlatform->getId(),
            ],
        ]);
    }
}
