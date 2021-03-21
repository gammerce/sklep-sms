<?php
namespace App\View\Html;

class Form extends DOMElement
{
    public function __construct($content = null, array $params = [])
    {
        parent::__construct("form", $content, $params);
    }
}
