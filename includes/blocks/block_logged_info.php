<?php

class BlockLoggedInfo extends BlockSimple implements I_BeLoggedMust
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
