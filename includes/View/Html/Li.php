<?php
namespace App\View\Html;

class Li extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("li", $content);
    }
}
