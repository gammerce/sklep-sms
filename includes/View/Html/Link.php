<?php
namespace App\View\Html;

class Link extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("a", $content);
    }
}
