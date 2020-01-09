<?php
namespace App\Http\Controllers\Api\Server;

use App\Http\Responses\XmlResponse;
use App\Http\Services\PurchaseService;
use App\ServiceModules\Interfaces\IServicePurchaseOutside;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class PurchaseResource
{
    public function post(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        PurchaseService $purchaseService
    ) {
        $lang = $translationManager->user();

        if (!$this->isCorrectlySigned($request, $settings['random_key'])) {
            return new Response("Invalid body sign");
        }

        $serviceModule = $heart->getServiceModule($request->request->get('service'));

        if ($serviceModule === null || !($serviceModule instanceof IServicePurchaseOutside)) {
            return new XmlResponse("bad_module", $lang->t('bad_module'), 0);
        }

        $response = $purchaseService->purchase($serviceModule, $request->request->all());

        return new XmlResponse(
            $response["status"],
            $response["text"],
            $response["positive"],
            $response["extraData"]
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
