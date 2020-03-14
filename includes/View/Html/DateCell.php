<?php
namespace App\View\Html;

class DateCell extends Cell
{
    public function __construct($date)
    {
        parent::__construct(convert_date($date));
    }
}
