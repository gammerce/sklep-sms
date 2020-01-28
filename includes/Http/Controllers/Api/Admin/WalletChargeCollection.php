<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Models\Purchase;
use App\Payment\AdminPaymentService;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class WalletChargeCollection
{
    public function post(
        $userId,
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        DatabaseLogger $logger,
        AdminPaymentService $adminPaymentService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $validator = new Validator(
            array_merge($request->request->all(), [
                'uid' => $userId,
            ]),
            [
                'uid' => [new RequiredRule(), new UserExistsRule()],
                'quantity' => [new RequiredRule(), new NumberRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $serviceModule = $heart->getServiceModule("charge_wallet");
        if (!$serviceModule) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        $editedUser = $heart->getUser($userId);
        $quantity = $validated['quantity'] * 100;

        // Zmiana wartości quantity, aby stan konta nie zszedł poniżej zera
        $quantity = max($quantity, -$editedUser->getWallet());

        // Dodawanie informacji o płatności do bazy
        $paymentId = $adminPaymentService->payByAdmin($user);

        // Kupujemy usługę
        $purchase = new Purchase($editedUser);
        $purchase->setPayment([
            Purchase::PAYMENT_METHOD => Purchase::METHOD_ADMIN,
            Purchase::PAYMENT_PAYMENT_ID => $paymentId,
        ]);
        $purchase->setOrder([
            Purchase::ORDER_QUANTITY => $quantity,
        ]);
        $purchase->setEmail($editedUser->getEmail());

        $serviceModule->purchase($purchase);

        $logger->logWithActor(
            'log_account_charged',
            $editedUser->getUsername(),
            $editedUser->getUid(),
            number_format($quantity / 100.0, 2),
            $settings->getCurrency()
        );

        return new ApiResponse(
            "charged",
            $lang->t(
                'account_charge_success',
                $editedUser->getUsername(),
                number_format($quantity / 100.0, 2),
                $settings->getCurrency()
            ),
            1
        );
    }
}
