<?php
namespace App\View\Html;

class InvoiceRef extends Link
{
    public function __construct($id)
    {
        parent::__construct("$id", "https://app.infakt.pl/app/faktury/{$id}");
    }
}
