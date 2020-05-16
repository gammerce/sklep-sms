<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\InvalidServiceModuleException;
use App\Http\Responses\ApiResponse;
use App\Http\Validation\Rules\NumberRule;
use App\Http\Validation\Rules\RequiredRule;
use App\Http\Validation\Rules\UserExistsRule;
use App\Http\Validation\Validator;
use App\Loggers\DatabaseLogger;
use App\Managers\ServiceModuleManager;
use App\Managers\UserManager;
use App\Models\Purchase;
use App\Payment\Admin\AdminPaymentService;
use App\Payment\General\PaymentMethod;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\Services\PriceTextService;
use App\System\Auth;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class WalletChargeCollection
{
    public function post(
        $userId,
        Request $request,
        UserManager $userManager,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings,
        DatabaseLogger $logger,
        ServiceModuleManager $serviceModuleManager,
        AdminPaymentService $adminPaymentService,
        PriceTextService $priceTextService
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $validator = new Validator(
            array_merge($request->request->all(), [
                "user_id" => $userId,
            ]),
            [
                "user_id" => [new RequiredRule(), new UserExistsRule()],
                "quantity" => [new RequiredRule(), new NumberRule()],
            ]
        );

        $validated = $validator->validateOrFail();

        $serviceModule = $serviceModuleManager->get(ChargeWalletServiceModule::MODULE_ID);
        if (!$serviceModule) {
            throw new InvalidServiceModuleException();
        }

        $editedUser = $userManager->getUser($userId);
        $quantity = price_to_int($validated["quantity"]);

        // Zmiana wartości quantity, aby stan konta nie zszedł poniżej zera
        $quantity = max($quantity, -$editedUser->getWallet());

        // Dodawanie informacji o płatności do bazy
        $paymentId = $adminPaymentService->payByAdmin($user);

        // Kupujemy usługę
        $purchase = (new Purchase($editedUser))
            ->setPayment([
                Purchase::PAYMENT_METHOD => PaymentMethod::ADMIN(),
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ])
            ->setOrder([
                Purchase::ORDER_QUANTITY => $quantity,
            ])
            ->setEmail($editedUser->getEmail());

        $serviceModule->purchase($purchase);

        $logger->logWithActor(
            "log_account_charged",
            $editedUser->getUsername(),
            $editedUser->getId(),
            $priceTextService->getPlainPrice($quantity),
            $settings->getCurrency()
        );

        return new ApiResponse(
            "charged",
            $lang->t(
                "account_charge_success",
                $editedUser->getUsername(),
                $priceTextService->getPlainPrice($quantity),
                $settings->getCurrency()
            ),
            1
        );
    }
}
