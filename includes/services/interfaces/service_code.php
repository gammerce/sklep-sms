<?php

use App\Models\Purchase;

/**
 * Obsługa płatności za pomocą kodu na usługę
 * Interface IService_ServiceCode
 */
interface IService_ServiceCode
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
