<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\ValidationException;
use App\Http\Responses\ServerResponseFactory;
use App\Http\Services\PurchaseService;
use App\Managers\ServiceModuleManager;
use App\Payment\Exceptions\PaymentProcessingException;
use App\Payment\General\PaymentResultType;
use App\ServiceModules\Interfaces\IServicePurchaseExternal;
use App\System\ServerAuth;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;
use UnexpectedValueException;

class PurchaseResource
{
    public function post(
        Request $request,
        ServiceModuleManager $serviceModuleManager,
        TranslationManager $translationManager,
        PurchaseService $purchaseService,
        ServerResponseFactory $responseFactory,
        ServerAuth $serverAuth
    ) {
        $acceptHeader = AcceptHeader::fromString($request->headers->get("Accept"));
        $lang = $translationManager->user();
        $server = $serverAuth->server();

        if (!$this->isCorrectlySigned($request, $server->getToken())) {
            return $responseFactory->create(
                $acceptHeader,
                "invalid_sign",
                "Invalid body sign",
                false
            );
        }

        $serviceModule = $serviceModuleManager->get($request->request->get("service_id"));
        if (!($serviceModule instanceof IServicePurchaseExternal)) {
            return $responseFactory->create(
                $acceptHeader,
                "bad_module",
                $lang->t("bad_module"),
                false
            );
        }

        try {
            $paymentResult = $purchaseService->purchase(
                $serviceModule,
                $server,
                $request->request->all()
            );
        } catch (ValidationException $e) {
            $warnings = $this->formatWarnings($e->warnings);
            $firstWarning = $this->getFirstWarning($e->warnings) ?: $lang->t("form_wrong_filled");

            return $responseFactory->create(
                $acceptHeader,
                "warnings",
                $firstWarning,
                false,
                compact("warnings")
            );
        } catch (PaymentProcessingException $e) {
            return $responseFactory->create($acceptHeader, $e->getCode(), $e->getMessage(), false);
        }

        if ($paymentResult->getType()->equals(PaymentResultType::PURCHASED())) {
            return $responseFactory->create(
                $acceptHeader,
                "purchased",
                $lang->t("purchase_success"),
                true,
                [
                    "bsid" => $paymentResult->getData(),
                ]
            );
        }

        throw new UnexpectedValueException("Unexpected payment result type");
    }

    private function isCorrectlySigned(Request $request, $secret)
    {
        $sign = $request->request->get("sign");
        $type = $request->request->get("type");
        $authData = $request->request->get("auth_data");
        $smsCode = $request->request->get("sms_code");

        $calculatedSign = md5(implode("#", [$type, $authData, $smsCode, $secret]));

        return $sign === $calculatedSign;
    }

    private function formatWarnings(array $warnings)
    {
        return collect($warnings)
            ->map(function ($warning, $key) {
                $text = implode("<br />", $warning);
                return "<strong>{$key}</strong><br />{$text}<br />";
            })
            ->join();
    }

    private function getFirstWarning(array $warnings)
    {
        foreach ($warnings as $field => $warning) {
            foreach ($warning as $text) {
                return "{$field}: $text";
            }
        }

        return null;
    }
}
