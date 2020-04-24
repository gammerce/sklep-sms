<?php
namespace App\View\Html;

class Link extends DOMElement
{
    public function __construct($content = null, $href = null, $target = null)
    {
        parent::__construct("a", $content);

        if ($href) {
            $this->setParam("href", $href);
        }

        if ($target) {
            $this->setParam("target", $target);
        }
    }
}
