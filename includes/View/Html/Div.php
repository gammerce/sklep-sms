<?php
namespace App\View\Html;

class Div extends DOMElement
{
    public function __construct($content = null, array $params = [])
    {
        parent::__construct("div", $content, $params);
    }
}
