<?php
namespace App\View\Html;

class Span extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("span", $content);
    }
}
