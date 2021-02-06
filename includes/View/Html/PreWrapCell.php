<?php
namespace App\View\Html;

class PreWrapCell extends Cell
{
    /**
     * @param string $text
     */
    public function __construct($text)
    {
        parent::__construct($text, "prewrap");
    }
}
