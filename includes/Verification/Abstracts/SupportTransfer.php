<?php
namespace App\Verification\Abstracts;

use App\Models\FinalizedPayment;
use App\Models\Purchase;

interface SupportTransfer
{
    /**
     * Przygotowanie zapytania POST w celu płatności przelewem
     *
     * @param Purchase $purchase
     *
     * @return array
     *  string url => adres url strony do ktorej wysylamy dane POST
     *  ... - wysyłane pola POST
     */
    public function prepareTransfer(Purchase $purchase);

    /**
     * Finalizuje zakup usługi podczas zakupu poprzez przelew
     *
     * @param array $query
     * @param array $body
     *
     * @return FinalizedPayment
     */
    public function finalizeTransfer(array $query, array $body);
}
