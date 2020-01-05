<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\ApiResponse;
use App\Models\Purchase;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\System\Auth;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PurchaseValidationResource
{
    public function post(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Auth $auth,
        Settings $settings
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        if (
            ($serviceModule = $heart->getServiceModule($request->request->get('service'))) ===
                null ||
            !($serviceModule instanceof IServicePurchaseWeb)
        ) {
            return new ApiResponse("wrong_module", $lang->t('bad_module'), 0);
        }

        // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
        if (!$heart->userCanUseService($user->getUid(), $serviceModule->service)) {
            return new ApiResponse("no_permission", $lang->t('service_no_permission'), 0);
        }

        // Przeprowadzamy walidację danych wprowadzonych w formularzu
        $returnData = $serviceModule->purchaseFormValidate($request->request->all());

        // Przerabiamy ostrzeżenia, aby lepiej wyglądały
        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        } else {
            //
            // Uzupełniamy brakujące dane
            /** @var Purchase $purchase */
            $purchase = $returnData['purchase_data'];

            if ($purchase->getService() === null) {
                $purchase->setService($serviceModule->service->getId());
            }

            if (!$purchase->getPayment('cost') && $purchase->getTariff() !== null) {
                $purchase->setPayment([
                    'cost' => $purchase->getTariff()->getProvision(),
                ]);
            }

            if (
                $purchase->getPayment('sms_platform') === null &&
                !$purchase->getPayment("no_sms") &&
                $settings->getSmsPlatformId()
            ) {
                $purchase->setPayment([
                    'sms_platform' => $settings->getSmsPlatformId(),
                ]);
            }

            // Ustawiamy taryfe z numerem
            if ($purchase->getPayment('sms_platform') !== null) {
                $paymentModule = $heart->getPaymentModuleByPlatformIdOrFail(
                    $purchase->getPayment('sms_platform')
                );
                $purchase->setTariff(
                    $paymentModule->getTariffById($purchase->getTariff()->getId())
                );
            }

            if ($purchase->getEmail() === null && strlen($user->getEmail())) {
                $purchase->setEmail($user->getEmail());
            }

            $purchaseEncoded = base64_encode(serialize($purchase));
            $returnData['data'] = [
                'length' => 8000,
                'data' => $purchaseEncoded,
                'sign' => md5($purchaseEncoded . $settings['random_key']),
            ];
        }

        return new ApiResponse(
            $returnData['status'],
            $returnData['text'],
            $returnData['positive'],
            $returnData['data']
        );
    }
}
