<?php
namespace App\View\Html;

class Select extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("select", $content);
    }
}
