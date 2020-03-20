<?php
namespace App\Http\Controllers\Api\Server;

use App\Exceptions\ValidationException;
use App\Http\Responses\ServerResponseFactory;
use App\Http\Services\PurchaseService;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\System\Heart;
use App\System\ServerAuth;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class PurchaseResource
{
    public function post(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        PurchaseService $purchaseService,
        ServerResponseFactory $responseFactory,
        ServerAuth $serverAuth
    ) {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        $lang = $translationManager->user();
        $server = $serverAuth->server();

        if ($server) {
            if (!$this->isCorrectlySigned($request, $server->getToken())) {
                return $responseFactory->create(
                    $acceptHeader,
                    "invalid_sign",
                    "Invalid body sign",
                    false
                );
            }
        } elseif (!$this->isCorrectlySigned($request, $settings->getSecret())) {
            return $responseFactory->create(
                $acceptHeader,
                "invalid_sign",
                "Invalid body sign",
                false
            );
        }

        $serviceModule = $heart->getServiceModule($request->request->get('service_id'));

        if (!($serviceModule instanceof IServicePurchaseOutside)) {
            return $responseFactory->create(
                $acceptHeader,
                "bad_module",
                $lang->t('bad_module'),
                false
            );
        }

        $body = $request->request->all();

        if ($server) {
            $body["server_id"] = $server->getId();
            $body["payment_platform_id"] =
                $server->getSmsPlatformId() ?: $settings->getSmsPlatformId();
        }

        try {
            $purchaseResult = $purchaseService->purchase($serviceModule, $body);
        } catch (ValidationException $e) {
            $warnings = $this->formatWarnings($e->warnings);
            $firstWarning = $this->getFirstWarning($e->warnings) ?: $lang->t('form_wrong_filled');

            return $responseFactory->create(
                $acceptHeader,
                "warnings",
                $firstWarning,
                false,
                compact('warnings')
            );
        }

        return $responseFactory->create(
            $acceptHeader,
            $purchaseResult->getStatus(),
            $purchaseResult->getText(),
            $purchaseResult->isPositive(),
            $purchaseResult->getData()
        );
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
