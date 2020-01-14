<?php
namespace App\ServiceModules\Interfaces;

use App\Models\Purchase;
use App\Models\ServiceCode;

/**
 * Obsługa płatności za pomocą kodu na usługę
 */
interface IServiceServiceCode
{
    /**
     * Sprawdza czy dany zakup może być zrealizowany ( opłacony ) przez dany kod na usługę
     *
     * @param Purchase      $purchase
     * @param ServiceCode   $serviceCode
     *
     * @return bool
     */
    public function serviceCodeValidate(Purchase $purchase, ServiceCode $serviceCode);
}
