<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\SqlQueryException;
use App\Http\Responses\ApiResponse;
use App\Http\Services\PriceService;
use App\Repositories\PriceListRepository;
use App\System\Auth;
use App\System\Database;
use App\Translation\TranslationManager;
use Symfony\Component\HttpFoundation\Request;

class PriceCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        PriceService $priceService,
        PriceListRepository $priceListRepository,
        Database $db
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $service = $request->request->get('service');
        $server = $request->request->get('server');
        $tariff = $request->request->get('tariff');
        $amount = $request->request->get('amount');

        $priceService->validateBody($request->request->all());

        try {
            $priceListRepository->create($service, $tariff, $amount, $server);
        } catch (SqlQueryException $e) {
            if ($e->getErrorno() == 1451) {
                return new ApiResponse(
                    "error",
                    $lang->translate('create_price_duplication'),
                    0
                );
            }

            throw $e;
        }

        log_to_db(
            "Admin {$user->getUsername()}({$user->getUid()}) dodał cenę. ID: " . $db->lastId()
        );

        return new ApiResponse('ok', $lang->translate('price_add'), 1);
    }
}
