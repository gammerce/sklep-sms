<?php
namespace App\Controllers\View;

use App\Heart;
use App\Models\Purchase;
use App\Payment;
use App\Settings;
use App\TranslationManager;
use App\Services\Interfaces\IServicePurchaseOutside;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServerStuffController
{
    public function action(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings
    ) {
        $lang = $translationManager->user();

        // Musi byc podany hash random_keya
        if ($request->get('key') != md5($settings['random_key'])) {
            return new Response();
        }

        $action = $request->get('action');

        if ($action == "purchase_service") {
            if (($serviceModule = $heart->getServiceModule($request->get('service'))) === null) {
                return $this->xmlOutput("bad_module", $lang->translate('bad_module'), 0);
            }

            if (!($serviceModule instanceof IServicePurchaseOutside)) {
                return $this->xmlOutput("bad_module", $lang->translate('bad_module'), 0);
            }

            // Sprawdzamy dane zakupu
            $purchaseData = new Purchase();
            $purchaseData->setService($serviceModule->service['id']);
            $purchaseData->user = $heart->getUser($request->get('uid'));
            $purchaseData->user->setPlatform($request->get('platform'));
            $purchaseData->user->setLastIp($request->get('ip'));
            $purchaseData->setOrder([
                'server' => $request->get('server'),
                'type' => $request->get('type'),
                'auth_data' => $request->get('auth_data'),
                'password' => $request->get('password'),
                'passwordr' => $request->get('password'),
            ]);
            $purchaseData->setPayment([
                'method' => $request->get('method'),
                'sms_code' => $request->get('sms_code'),
                'sms_service' => $request->get('transaction_service'),
            ]);

            // Ustawiamy taryfę z numerem
            $payment = new Payment($purchaseData->getPayment('sms_service'));
            $purchaseData->setTariff(
                $payment->getPaymentModule()->getTariffById($request->get('tariff'))
            );

            $returnValidation = $serviceModule->purchaseDataValidate($purchaseData);

            // Są jakieś błędy przy sprawdzaniu danych
            if ($returnValidation['status'] != "ok") {
                $extraData = '';
                if (!empty($returnValidation["data"]["warnings"])) {
                    $warnings = '';
                    foreach ($returnValidation["data"]["warnings"] as $what => $warning) {
                        $warnings .=
                            "<strong>{$what}</strong><br />" .
                            implode("<br />", $warning) .
                            "<br />";
                    }

                    if (strlen($warnings)) {
                        $extraData .= "<warnings>{$warnings}</warnings>";
                    }
                }

                return $this->xmlOutput(
                    $returnValidation['status'],
                    $returnValidation['text'],
                    $returnValidation['positive'],
                    $extraData
                );
            }

            /** @var Purchase $purchaseData */
            $purchaseData = $returnValidation['purchase_data'];
            $purchaseData->setPayment([
                'method' => $request->get('method'),
                'sms_code' => $request->get('sms_code'),
                'sms_service' => $request->get('transaction_service'),
            ]);
            $returnPayment = make_payment($purchaseData);

            $extraData = "";

            if (isset($returnPayment['data']['bsid'])) {
                $extraData .= "<bsid>{$returnPayment['data']['bsid']}</bsid>";
            }

            if (isset($returnPayment["data"]["warnings"])) {
                $warnings = "";
                foreach ($returnPayment["data"]["warnings"] as $what => $text) {
                    $warnings .= "<strong>{$what}</strong><br />{$text}<br />";
                }

                if (strlen($warnings)) {
                    $extraData .= "<warnings>{$warnings}</warnings>";
                }
            }

            return $this->xmlOutput(
                $returnPayment['status'],
                $returnPayment['text'],
                $returnPayment['positive'],
                $extraData
            );
        }

        return $this->xmlOutput("script_error", "An error occured: no action.", false);
    }

    protected function xmlOutput($returnValue, $text, $positive, $extraData = "")
    {
        $output = "<return_value>{$returnValue}</return_value>";
        $output .= "<text>{$text}</text>";
        $output .= "<positive>{$positive}</positive>";
        $output .= $extraData;

        return new Response($output, 200, [
            'Content-type' => 'text/plain; charset="UTF-8"',
        ]);
    }
}
