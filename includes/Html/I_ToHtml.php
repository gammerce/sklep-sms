<?php
namespace App\Html;

interface I_ToHtml
{
    /**
     * Tworzy kod html elementu
     *
     * @return string
     */
    public function toHtml();
}
