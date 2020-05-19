<?php
namespace App\View\Html;

class DateTimeCell extends Cell
{
    public function __construct($date)
    {
        parent::__construct(as_datetime_string($date), "date");
    }
}
