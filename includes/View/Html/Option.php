<?php
namespace App\View\Html;

class Option extends DOMElement
{
    public function __construct($content = null, $value = null, array $params = [])
    {
        parent::__construct("option", $content, $params);

        if ($value !== null) {
            $this->setParam("value", $value);
        }
    }
}
