<?php
namespace App\Kernels;

use App\Heart;
use App\Middlewares\IsUpToDate;
use App\Middlewares\LicenseIsValid;
use App\Middlewares\LoadSettings;
use App\Middlewares\ManageAuthentication;
use App\Middlewares\SetLanguage;
use App\Models\Purchase;
use App\Payment;
use App\Settings;
use App\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ServersStuffKernel extends Kernel
{
    protected $middlewares = [
        IsUpToDate::class,
        LoadSettings::class,
        SetLanguage::class,
        ManageAuthentication::class,
        LicenseIsValid::class,
    ];

    public function run(Request $request)
    {
        /** @var Heart $heart */
        $heart = $this->app->make(Heart::class);

        /** @var Settings $settings */
        $settings = $this->app->make(Settings::class);

        /** @var TranslationManager $translationManager */
        $translationManager = $this->app->make(TranslationManager::class);
        $lang = $translationManager->user();

        // Musi byc podany hash random_keya
        if ($request->get('key') != md5($settings['random_key'])) {
            return new Response();
        }

        $action = $request->get('action');

        if ($action == "purchase_service") {
            if (($service_module = $heart->get_service_module($request->get('service'))) === null) {
                return $this->xmlOutput("bad_module", $lang->translate('bad_module'), 0);
            }

            if (!object_implements($service_module, "IService_PurchaseOutside")) {
                return $this->xmlOutput("bad_module", $lang->translate('bad_module'), 0);
            }

            // Sprawdzamy dane zakupu
            $purchaseData = new Purchase();
            $purchaseData->setService($service_module->service['id']);
            $purchaseData->user = $heart->get_user($request->get('uid'));
            $purchaseData->user->setPlatform($request->get('platform'));
            $purchaseData->user->setLastip($request->get('ip'));
            $purchaseData->setOrder([
                'server'    => $request->get('server'),
                'type'      => $request->get('type'),
                'auth_data' => $request->get('auth_data'),
                'password'  => $request->get('password'),
                'passwordr' => $request->get('password'),
            ]);
            $purchaseData->setPayment([
                'method'      => $request->get('method'),
                'sms_code'    => $request->get('sms_code'),
                'sms_service' => $request->get('transaction_service'),
            ]);

            // Ustawiamy taryfę z numerem
            $payment = new Payment($purchaseData->getPayment('sms_service'));
            $purchaseData->setTariff($payment->getPaymentModule()->getTariffById($request->get('tariff')));

            $returnValidation = $service_module->purchase_data_validate($purchaseData);

            // Są jakieś błędy przy sprawdzaniu danych
            if ($returnValidation['status'] != "ok") {
                $extraData = '';
                if (!empty($returnValidation['data']['warnings'])) {
                    $warnings = '';
                    foreach ($returnValidation['data']['warnings'] as $what => $warning) {
                        $warnings .= "<strong>{$what}</strong><br />" . implode("<br />", $warning) . "<br />";
                    }

                    if (strlen($warnings)) {
                        $extraData .= "<warnings>{$warnings}</warnings>";
                    }
                }

                return $this->xmlOutput(
                    $returnValidation['status'], $returnValidation['text'], $returnValidation['positive'], $extraData
                );
            }

            /** @var Purchase $purchaseData */
            $purchaseData = $returnValidation['purchase_data'];
            $purchaseData->setPayment([
                'method'      => $request->get('method'),
                'sms_code'    => $request->get('sms_code'),
                'sms_service' => $request->get('transaction_service'),
            ]);
            $returnPayment = validate_payment($purchaseData);

            $extraData = "";

            if (isset($returnPayment['data']['bsid'])) {
                $extraData .= "<bsid>{$returnPayment['data']['bsid']}</bsid>";
            }

            if (isset($returnPayment['data']['warnings'])) {
                $warnings = "";
                foreach ($returnPayment['data']['warnings'] as $what => $text) {
                    $warnings .= "<strong>{$what}</strong><br />{$text}<br />";
                }

                if (strlen($warnings)) {
                    $extraData .= "<warnings>{$warnings}</warnings>";
                }
            }

            return $this->xmlOutput(
                $returnPayment['status'], $returnPayment['text'], $returnPayment['positive'], $extraData
            );
        }

        return $this->xmlOutput("script_error", "An error occured: no action.", false);
    }

    protected function xmlOutput($return_value, $text, $positive, $extra_data = "")
    {
        $output = "<return_value>{$return_value}</return_value>";
        $output .= "<text>{$text}</text>";
        $output .= "<positive>{$positive}</positive>";
        $output .= $extra_data;

        return new Response($output, 200, [
            'Content-type' => 'text/plain; charset="UTF-8"',
        ]);
    }
}
