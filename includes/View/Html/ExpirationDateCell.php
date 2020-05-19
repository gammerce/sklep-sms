<?php
namespace App\View\Html;

class ExpirationDateCell extends Cell
{
    public function __construct($date)
    {
        parent::__construct(as_expiration_date_string($date), "date");
    }
}
