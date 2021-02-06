<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;
use App\Models\Transaction;

/**
 * Possibility to purchase the service via the website
 */
interface IServicePurchaseWeb extends IServicePurchase
{
    /**
     * Returns a purchase fom
     *
     * @param array $query
     * @return string
     */
    public function purchaseFormGet(array $query): string;

    /**
     * Method called when the data provided by a user in the purchase form
     * needs to be validated
     *
     * @param Purchase $purchase
     * @param array $body
     */
    public function purchaseFormValidate(Purchase $purchase, array $body): void;

    /**
     * Returns the order details, displayed when purchasing the service, before payment.
     *
     * @param Purchase $purchase
     * @return string
     */
    public function orderDetails(Purchase $purchase): string;

    /**
     * Formats and returns information about the purchased service.
     *
     * @param string $action What will the data be used for (email, web, payment_log)
     *   email - a message sent to the email about the purchase of the service
     *   web - information displayed on the website right after the purchase
     *   payment_log - entry in the payment history
     * @param Transaction $transaction
     * @return string|array
     */
    public function purchaseInfo($action, Transaction $transaction);
}
