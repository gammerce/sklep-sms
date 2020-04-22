<?php
namespace App\View\Html;

class Row extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("tr", $content);
    }
}
