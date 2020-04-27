<?php
namespace App\View\Pages;

use App\View\Interfaces\IBeLoggedMust;

abstract class PageAdmin extends Page implements IBeLoggedMust
{
    public function getPrivilege()
    {
        return "acp";
    }

    public function getPagePath()
    {
        return "/admin/{$this->getId()}";
    }
}
