<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
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

        $amount = intval($request->request->get('amount')) * 100;

        $warnings = [];

        // ID użytkownika
        if ($warning = check_for_warnings("uid", $userId)) {
            $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
        } else {
            $editedUser = $heart->getUser($userId);
            if (!$editedUser->exists()) {
                $warnings['uid'][] = $lang->t('noaccount_id');
            }
        }

        // Wartość Doładowania
        if (!$amount) {
            $warnings['amount'][] = $lang->t('no_charge_value');
        } elseif (!is_numeric($amount)) {
            $warnings['amount'][] = $lang->t('charge_number');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $serviceModule = $heart->getServiceModule("charge_wallet");
        if ($serviceModule === null) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        // Zmiana wartości amount, aby stan konta nie zszedł poniżej zera
        $amount = max($amount, -$editedUser->getWallet());

        // Dodawanie informacji o płatności do bazy
        $paymentId = $adminPaymentService->payByAdmin($user);

        // Kupujemy usługę
        $purchase = new Purchase($editedUser);
        $purchase->setPayment([
            'method' => "admin",
            'payment_id' => $paymentId,
        ]);
        $purchase->setOrder([
            'amount' => $amount,
        ]);
        $purchase->setEmail($editedUser->getEmail());

        $serviceModule->purchase($purchase);

        $logger->logWithActor(
            'log_account_charged',
            $editedUser->getUsername(),
            $editedUser->getUid(),
            number_format($amount / 100.0, 2),
            $settings->getCurrency()
        );

        return new ApiResponse(
            "charged",
            $lang->t(
                'account_charge_success',
                $editedUser->getUsername(),
                number_format($amount / 100.0, 2),
                $settings->getCurrency()
            ),
            1
        );
    }
}
