<?php
namespace App\View\Html;

class Script extends DOMElement
{
    public function __construct($content = null)
    {
        parent::__construct("script", new RawHtml($content), [
            "type" => "text/javascript",
        ]);
    }
}
