<?php
namespace App\View\Html;

class NoneText extends Span
{
    public function __construct()
    {
        parent::__construct(__("none"));
        $this->addClass("text-muted");
    }
}
