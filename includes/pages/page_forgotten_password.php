<?php

$heart->register_page("forgotten_password", "PageForgottenPassword");

class PageForgottenPassword extends PageSimple
{

	protected $template = "forgotten_password";
	protected $require_login = -1;

	function __construct()
	{
		global $lang;
		$this->title = $lang['forgotten_password'];

		parent::__construct();
	}

	protected function content($get, $post)
	{
		global $settings, $scripts;
		$scripts[] = $settings['shop_url_slash'] . "jscripts/modify_password.js?version=" . VERSION;

		return parent::content($get, $post);
	}

}