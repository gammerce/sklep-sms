<?php
namespace App\Blocks;

use App\Interfaces\IBeLoggedMust;

class BlockLoggedInfo extends BlockSimple implements IBeLoggedMust
{
    protected $template = "logged_in_informations";

    public function get_content_class()
    {
        return "logged_info";
    }

    public function get_content_id()
    {
        return "logged_info";
    }
}
