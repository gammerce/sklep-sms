<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;

/**
 * Obsługa płatności za pomocą kodu na usługę
 */
interface IServiceServiceCode
{
    /**
     * Sprawdza czy dany zakup może być zrealizowany ( opłacony ) przez dany kod na usługę
     *
     * @param Purchase $purchase
     * @param array    $code
     *
     * @return bool
     */
    public function serviceCodeValidate(Purchase $purchase, $code);
}
