<?php
namespace App\View\Html;

class Option extends DOMElement
{
    public function __construct($content = null, $value = null)
    {
        parent::__construct("option", $content);

        if ($value !== null) {
            $this->setParam("value", $value);
        }
    }
}
