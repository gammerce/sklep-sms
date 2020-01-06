<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidPaymentModuleException;
use App\Exceptions\ValidationException;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PaymentPlatformService;
use App\Repositories\PaymentPlatformRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentPlatformCollection
{
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

        return new SuccessApiResponse($lang->t('payment_platform_added'), [
            'data' => [
                'id' => $paymentPlatform->getId(),
            ],
        ]);
    }
}
