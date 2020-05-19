<?php
namespace App\View\Html;

class PlatformCell extends Cell
{
    public function __construct($platform)
    {
        parent::__construct((new Div(get_platform($platform)))->addClass("one_line"), "platform");
    }
}
