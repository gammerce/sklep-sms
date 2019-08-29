<?php
namespace App\Services\Interfaces;

use App\Models\Purchase;

/**
 * Obsługa płatności za pomocą kodu na usługę
 */
interface IServiceServiceCode
{
    /**
     * Sprawdza czy dany zakup może być zrealizowany ( opłacony ) przez dany kod na usługę
     *
     * @param Purchase $purchaseData
     * @param array    $code
     *
     * @return bool
     */
    public function serviceCodeValidate($purchaseData, $code);
}
