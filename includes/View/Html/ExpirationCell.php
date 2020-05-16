<?php
namespace App\View\Html;

class ExpirationCell extends Cell
{
    public function __construct($date)
    {
        parent::__construct(as_expiration_datetime_string($date));
    }
}
