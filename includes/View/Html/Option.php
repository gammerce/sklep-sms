<?php
namespace App\View\Html;

class Option extends DOMElement
{
    protected $name = 'option';

    public function __construct($content = null, $value = null)
    {
        parent::__construct($content);

        if ($value !== null) {
            $this->setParam("value", $value);
        }
    }
}
