<?php
namespace App\View\Html;

class PlainText implements I_ToHtml
{
    /** @var  string */
    private $text;

    /**
     * @param string $text
     */
    public function __construct($text)
    {
        $this->text = (string) $text;
    }

    public function toHtml()
    {
        return htmlspecialchars($this->text);
    }

    public function __toString()
    {
        return $this->toHtml();
    }
}
