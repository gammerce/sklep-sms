<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ApiResponse;
use App\Http\Services\PriceService;
use App\Repositories\PriceRepository;
use App\System\Auth;
use App\Translation\TranslationManager;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

class PriceCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        Auth $auth,
        PriceService $priceService,
        PriceRepository $priceRepository
    ) {
        $lang = $translationManager->user();
        $user = $auth->user();

        $service = $request->request->get('service');
        $server = $request->request->get('server');
        $tariff = $request->request->get('tariff');
        $amount = $request->request->get('amount');

        $priceService->validateBody($request->request->all());

        try {
            $price = $priceRepository->create($service, $tariff, $amount, $server);
        } catch (PDOException $e) {
            // Duplication
            if (get_error_code($e) === 1062) {
                return new ApiResponse("error", $lang->translate('create_price_duplication'), 0);
            }

            throw $e;
        }

        log_to_db(
            "Admin {$user->getUsername()}({$user->getUid()}) dodał cenę. ID: " . $price->getId()
        );

        return new ApiResponse('ok', $lang->translate('price_add'), 1, [
            'data' => [
                'id' => $price->getId(),
            ],
        ]);
    }
}
