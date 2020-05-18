<?php
namespace App\View\Html;

class InfoTitle extends Span
{
    public function __construct($content = null)
    {
        parent::__construct($content);
        $this->addClass("info-title");
    }
}
