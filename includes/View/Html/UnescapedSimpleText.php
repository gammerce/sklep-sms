<?php
namespace App\View\Html;

class UnescapedSimpleText implements I_ToHtml
{
    /** @var string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = strval($text);
    }

    public function toHtml()
    {
        return $this->text;
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
