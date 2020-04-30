<?php
namespace App\View\Pages\Admin;

use App\View\Interfaces\IBeLoggedMust;
use App\View\Pages\Page;

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
