<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Repositories\PaymentPlatformRepository;
use App\System\Auth;
use App\System\Heart;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PaymentPlatformCollection
{
    public function post(
        Request $request,
        PaymentPlatformRepository $repository,
        Auth $auth,
        TranslationManager $translationManager,
        Heart $heart
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();
        $name = $request->request->get("name");
        $module = $request->request->get("module");
        $data = $request->request->get("data");

        $paymentModule = $heart->getPaymentModule($module);

        if (!$paymentModule) {
            throw new ValidationException([
                "module" => "Invalid module ID",
            ]);
        }

        // TODO Validate data

        $paymentPlatform = $repository->create($name, $module, $data);

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
