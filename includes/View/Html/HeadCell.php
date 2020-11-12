<?php
namespace App\View\Html;

class HeadCell extends DOMElement
{
    public function __construct($content = null, $headers = null)
    {
        parent::__construct("th", $content);

        if ($headers) {
            $this->setParam("headers", $headers);
        }
    }
}
