<?php
namespace App\Http\Controllers\Api\Shop;

use App\Http\Responses\PlainResponse;
use App\Payment\General\PurchaseInformation;

class PurchaseResource
{
    public function get($purchaseId, PurchaseInformation $purchaseInformation)
    {
        return new PlainResponse(
            $purchaseInformation->get([
                'purchase_id' => $purchaseId,
                'action' => "web",
            ])
        );
    }
}
