<?php
namespace App\View\Html;

class Cell extends DOMElement
{
    public function __construct($content = null, $headers = null)
    {
        parent::__construct("td", $content);

        if ($headers) {
            $this->setParam('headers', $headers);
        }
    }
}
