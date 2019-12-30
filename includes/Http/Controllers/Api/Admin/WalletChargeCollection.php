<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\ValidationException;
use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
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
        Settings $settings
    ) {
        $lang = $translationManager->user();
        $langShop = $translationManager->shop();
        $user = $auth->user();

        $amount = intval($request->request->get('amount')) * 100;

        $warnings = [];

        // ID użytkownika
        if ($warning = check_for_warnings("uid", $userId)) {
            $warnings['uid'] = array_merge((array) $warnings['uid'], $warning);
        } else {
            $editedUser = $heart->getUser($userId);
            if (!$editedUser->exists()) {
                $warnings['uid'][] = $lang->translate('noaccount_id');
            }
        }

        // Wartość Doładowania
        if (!$amount) {
            $warnings['amount'][] = $lang->translate('no_charge_value');
        } elseif (!is_numeric($amount)) {
            $warnings['amount'][] = $lang->translate('charge_number');
        }

        if ($warnings) {
            throw new ValidationException($warnings);
        }

        $serviceModule = $heart->getServiceModule("charge_wallet");
        if ($serviceModule === null) {
            return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
        }

        // Zmiana wartości amount, aby stan konta nie zszedł poniżej zera
        $amount = max($amount, -$editedUser->getWallet());

        // Dodawanie informacji o płatności do bazy
        $paymentId = pay_by_admin($user);

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

        log_to_db(
            $langShop->sprintf(
                $langShop->translate('account_charge'),
                $user->getUsername(),
                $user->getUid(),
                $editedUser->getUsername(),
                $editedUser->getUid(),
                number_format($amount / 100.0, 2),
                $settings['currency']
            )
        );

        return new ApiResponse(
            "charged",
            $lang->sprintf(
                $lang->translate('account_charge_success'),
                $editedUser->getUsername(),
                number_format($amount / 100.0, 2),
                $settings['currency']
            ),
            1
        );
    }
}
