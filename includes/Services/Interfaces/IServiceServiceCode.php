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
     * @param Purchase $purchase_data
     * @param array    $code
     *
     * @return bool
     */
    public function service_code_validate($purchase_data, $code);
}
