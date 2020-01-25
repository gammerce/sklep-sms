<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\ServerResponseFactory;
use App\Http\Services\PurchaseService;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\System\Heart;
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
        ServerResponseFactory $responseFactory
    ) {
        $acceptHeader = AcceptHeader::fromString($request->headers->get('Accept'));
        $lang = $translationManager->user();

        if (!$this->isCorrectlySigned($request, $settings->getSecret())) {
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

        $response = $purchaseService->purchase($serviceModule, $request->request->all());

        return $responseFactory->create(
            $acceptHeader,
            array_get($response, 'status'),
            array_get($response, 'text'),
            array_get($response, 'positive'),
            (array) array_get($response, 'extraData', [])
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
}
