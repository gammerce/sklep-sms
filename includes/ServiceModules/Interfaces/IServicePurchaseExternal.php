<?php
namespace App\ServiceModules\Interfaces;

use App\Http\Validation\Validator;
use App\Models\Purchase;

/**
 * Service can be bought from outside ( eg. on server )
 */
interface IServicePurchaseExternal extends IServicePurchase
{
    /**
     * Validates provided purchase data
     * Called when purchase was made from outside, not via WWW
     *
     * @param Purchase $purchase
     * @return Validator
     */
    public function purchaseDataValidate(Purchase $purchase): Validator;
}
