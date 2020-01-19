<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Responses\ErrorApiResponse;
use App\Http\Responses\SuccessApiResponse;
use App\Http\Services\PriceService;
use App\Loggers\DatabaseLogger;
use App\Repositories\PriceRepository;
use App\Translation\TranslationManager;
use PDOException;
use Symfony\Component\HttpFoundation\Request;

class PriceCollection
{
    public function post(
        Request $request,
        TranslationManager $translationManager,
        PriceService $priceService,
        PriceRepository $priceRepository,
        DatabaseLogger $logger
    ) {
        $lang = $translationManager->user();

        $serviceId = $request->request->get('service_id');
        $serverId = $request->request->get('server_id') ?: null;
        $quantity = $request->request->get('quantity') ?: null;

        if (strlen($request->request->get('sms_price'))) {
            $smsPrice = (int) $request->request->get('sms_price');
        } else {
            $smsPrice = null;
        }

        if (strlen($request->request->get('transfer_price'))) {
            $transferPrice = $request->request->get('transfer_price') * 100;
        } else {
            $transferPrice = null;
        }

        $priceService->validateBody($request->request->all());

        try {
            $price = $priceRepository->create(
                $serviceId,
                $serverId,
                $smsPrice,
                $transferPrice,
                $quantity
            );
        } catch (PDOException $e) {
            // Duplication
            if (get_error_code($e) === 1062) {
                return new ErrorApiResponse($lang->t('create_price_duplication'));
            }

            throw $e;
        }

        $logger->logWithActor("log_price_added", $price->getId());

        return new SuccessApiResponse($lang->t('price_add'), [
            'data' => [
                'id' => $price->getId(),
            ],
        ]);
    }
}
