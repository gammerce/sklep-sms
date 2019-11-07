<?php
namespace App\Http\Controllers\Api;

use App\Http\Responses\PlainResponse;

class PurchaseResource
{
    public function get($purchaseId)
    {
        return new PlainResponse(
            purchase_info([
                'purchase_id' => $purchaseId,
                'action' => "web",
            ])
        );
    }
}
