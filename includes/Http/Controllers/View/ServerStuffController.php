<?php
namespace App\Http\Controllers\View;

use App\Http\Responses\XmlResponse;
use App\Http\Services\PurchaseService;
use App\Services\Interfaces\IServicePurchaseOutside;
use App\System\Heart;
use App\System\Settings;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @deprecated
 */
class ServerStuffController
{
    public function action(
        Request $request,
        Heart $heart,
        TranslationManager $translationManager,
        Settings $settings,
        PurchaseService $purchaseService
    ) {
        $lang = $translationManager->user();

        $key = $request->query->get('key');
        $service = $request->query->get('service');

        if ($key != md5($settings['random_key'])) {
            return new Response();
        }

        $serviceModule = $heart->getServiceModule($service);

        if ($serviceModule === null || !($serviceModule instanceof IServicePurchaseOutside)) {
            return new XmlResponse("bad_module", $lang->t('bad_module'), 0);
        }

        $response = $purchaseService->purchase($serviceModule, $request->query->all());

        return new XmlResponse(
            $response["status"],
            $response["text"],
            $response["positive"],
            $response["extraData"]
        );
    }
}
