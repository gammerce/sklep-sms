<?php
namespace App\View\Html;

class Tbody extends DOMElement
{
    public function __construct($content = null, array $params = [])
    {
        parent::__construct("tbody", $content, $params);
    }
}
