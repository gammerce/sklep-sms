<?php
namespace App\Html;

class SimpleText implements I_ToHtml
{
    /** @var  string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = strval($text);
    }

    /**
     * Tworzy kod html elementu
     *
     * @return string
     */
    public function toHtml()
    {
        return $this->text;
    }
}
