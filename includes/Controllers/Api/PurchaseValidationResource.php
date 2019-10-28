<?php
namespace App\Controllers\Api;

use App\Auth;
use App\Heart;
use App\Models\Purchase;
use App\Payment;
use App\Responses\ApiResponse;
use App\Services\Interfaces\IServicePurchaseWeb;
use App\Settings;
use App\TranslationManager;
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
            return new ApiResponse("wrong_module", $lang->translate('bad_module'), 0);
        }

        // Użytkownik nie posiada grupy, która by zezwalała na zakup tej usługi
        if (!$heart->userCanUseService($user->getUid(), $serviceModule->service)) {
            return new ApiResponse("no_permission", $lang->translate('service_no_permission'), 0);
        }

        // Przeprowadzamy walidację danych wprowadzonych w formularzu
        $returnData = $serviceModule->purchaseFormValidate($request->request->all());

        // Przerabiamy ostrzeżenia, aby lepiej wyglądały
        if ($returnData['status'] == "warnings") {
            $returnData["data"]["warnings"] = format_warnings($returnData["data"]["warnings"]);
        } else {
            //
            // Uzupełniamy brakujące dane
            /** @var Purchase $purchaseData */
            $purchaseData = $returnData['purchase_data'];

            if ($purchaseData->getService() === null) {
                $purchaseData->setService($serviceModule->service['id']);
            }

            if (!$purchaseData->getPayment('cost') && $purchaseData->getTariff() !== null) {
                $purchaseData->setPayment([
                    'cost' => $purchaseData->getTariff()->getProvision(),
                ]);
            }

            if (
                $purchaseData->getPayment('sms_service') === null &&
                !$purchaseData->getPayment("no_sms") &&
                strlen($settings['sms_service'])
            ) {
                $purchaseData->setPayment([
                    'sms_service' => $settings['sms_service'],
                ]);
            }

            // Ustawiamy taryfe z numerem
            if ($purchaseData->getPayment('sms_service') !== null) {
                $payment = new Payment($purchaseData->getPayment('sms_service'));
                $purchaseData->setTariff(
                    $payment->getPaymentModule()->getTariffById($purchaseData->getTariff()->getId())
                );
            }

            if ($purchaseData->getEmail() === null && strlen($user->getEmail())) {
                $purchaseData->setEmail($user->getEmail());
            }

            $purchaseDataEncoded = base64_encode(serialize($purchaseData));
            $returnData['data'] = [
                'length' => 8000,
                'data' => $purchaseDataEncoded,
                'sign' => md5($purchaseDataEncoded . $settings['random_key']),
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
