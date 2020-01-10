<?php
namespace App\View\Html;

interface I_ToHtml
{
    /**
     * Tworzy kod html elementu
     *
     * @return string
     */
    public function toHtml();
}
