<?php

$heart->register_page("change_password", "PageChangePassword");

class PageChangePassword extends PageSimple
{

	protected $template = "change_password";
	protected $require_login = 1;
	protected $title = "Zmiana hasła";

	function __construct()
	{
		parent::__construct();

		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/modify_password.js?version=" . VERSION;
	}

}