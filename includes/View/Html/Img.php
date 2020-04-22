<?php
namespace App\View\Html;

class Img extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("img", $content);
    }
}
