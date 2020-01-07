<?php
namespace App\Http\Controllers\Api\Admin;

use App\Exceptions\EntityNotFoundException;
use App\Exceptions\InvalidPaymentModuleException;
use App\Http\Responses\HtmlResponse;
use App\Http\Services\DataFieldService;

class PaymentModuleAddFormController
{
    public function get($paymentModuleId, DataFieldService $dataFieldService)
    {
        try {
            return new HtmlResponse($dataFieldService->renderDataFields($paymentModuleId, []));
        } catch (InvalidPaymentModuleException $e) {
            throw new EntityNotFoundException();
        }
    }
}
