<?php

$heart->register_block("logged_info", "BlockLoggedInfo");

class PageLoggedInfo extends BlockSimple
{

	protected $template = "logged_in_informations";
	protected $require_login = 1;

	public function get_content_class()
	{
		return "logged_info";
	}

	public function get_content_id()
	{
		return "logged_info";
	}

}