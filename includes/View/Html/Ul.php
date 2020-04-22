<?php
namespace App\View\Html;

class Ul extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("ul", $content);
    }
}
