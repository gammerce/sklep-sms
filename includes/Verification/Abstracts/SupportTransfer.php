<?php
namespace App\Verification\Abstracts;

use App\Models\Purchase;
use App\Models\TransferFinalize;

interface SupportTransfer
{
    /**
     * Przygotowanie zapytania POST w celu płatności przelewem
     *
     * @param Purchase $purchase
     * @param string   $dataFilename
     *
     * @return array
     *  string url => adres url strony do ktorej wysylamy dane POST
     *  ... - wysyłane pola POST
     */
    public function prepareTransfer(Purchase $purchase, $dataFilename);

    /**
     * Finalizuje zakup usługi podczas zakupu poprzez przelew
     *
     * @param array $query
     * @param array $body
     *
     * @return TransferFinalize
     */
    public function finalizeTransfer($query, $body);
}
