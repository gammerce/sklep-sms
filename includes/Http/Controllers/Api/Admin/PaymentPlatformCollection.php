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
        $user = $auth->user();
        $name = $request->request->get("name");
        $platform = $request->request->get("platform");
        $data = $request->request->get("data");

        $paymentModulesIds = $heart->getPaymentModulesIds();

        if (!in_array($platform, $paymentModulesIds)) {
            throw new ValidationException([
                "platform" => "Invalid platform ID",
            ]);
        }

        $paymentPlatform = $repository->create($name, $platform, $data);

        log_to_db(
            "Admin {$user->getUsername()}({$user->getUid()}) dodał platformę płatnosci. ID: " .
                $paymentPlatform->getId()
        );

        return new ApiResponse('ok', $lang->translate('payment_platform_add'), true, [
            'data' => [
                'id' => $paymentPlatform->getId(),
            ],
        ]);
    }
}
