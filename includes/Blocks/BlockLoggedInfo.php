<?php
namespace App\Blocks;

use App\Interfaces\IBeLoggedMust;

class BlockLoggedInfo extends BlockSimple implements IBeLoggedMust
{
    protected $template = "logged_in_informations";

    public function getContentClass()
    {
        return "logged_info";
    }

    public function getContentId()
    {
        return "logged_info";
    }
}
