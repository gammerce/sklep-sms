<?php
namespace App\View\Html;

class Input extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("input", $content);
    }
}
