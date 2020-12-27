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
use App\Payment\General\PaymentOption;
use App\ServiceModules\ChargeWallet\ChargeWalletServiceModule;
use App\Support\PriceTextService;
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

        $editedUser = $userManager->get($userId);
        $quantity = price_to_int($validated["quantity"]);

        // Make sure wallet value is non-negative after top-up
        $quantity = max($quantity, -$editedUser->getWallet()->asInt());

        $paymentId = $adminPaymentService->payByAdmin(
            $user,
            get_ip($request),
            get_platform($request)
        );

        $purchase = (new Purchase($editedUser, get_ip($request), get_platform($request)))
            ->setPayment([
                Purchase::PAYMENT_PAYMENT_ID => $paymentId,
            ])
            ->setPaymentOption(new PaymentOption(PaymentMethod::ADMIN()))
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
