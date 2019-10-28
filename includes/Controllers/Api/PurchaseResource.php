<?php
namespace App\Controllers\Api;

use App\Responses\PlainResponse;

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
