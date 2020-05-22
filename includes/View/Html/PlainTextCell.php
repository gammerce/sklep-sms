<?php
namespace App\View\Html;

class PlainTextCell extends Cell
{
    /**
     * @param string $text
     * @param string|null $headers
     */
    public function __construct($text, $headers = null)
    {
        parent::__construct($text, $headers);
        $this->setParam("title", $text);
    }
}
