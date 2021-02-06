<?php
namespace App\View\Html;

class RawHtml implements I_ToHtml
{
    private string $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = (string) $text;
    }

    public function toHtml(): string
    {
        return $this->text;
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
